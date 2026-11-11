<?php

namespace App\Domain\Crm\Services;

use App\Domain\Crm\Models\DemoRequest;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Models\LeadActivity;
use App\Models\User;
use App\Support\Platform\PlatformEventNotifier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class DemoRequestService
{
    public function __construct(
        private readonly LeadService $leads,
        private readonly PlatformEventNotifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = DemoRequest::query()
            ->with(['assignee:id,name,email', 'lead:id,pharmacy_name,status,email,address', 'reviewer:id,name'])
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('pharmacy_name', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Public website submission.
     *
     * @param  array<string, mixed>  $data
     */
    public function submitPublic(array $data): DemoRequest
    {
        return DB::transaction(function () use ($data) {
            $lead = $this->leads->create([
                'pharmacy_name' => $data['pharmacy_name'],
                'owner_name' => $data['owner_name'],
                'phone' => $data['mobile'],
                'whatsapp' => $data['mobile'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'branch_count' => $data['branch_count'] ?? 1,
                'source' => 'website',
                'status' => Lead::STATUS_NEW,
                'notes' => $data['message'] ?? null,
                'follow_up_at' => $data['preferred_at'] ?? null,
            ]);

            $demo = DemoRequest::query()->create([
                'lead_id' => $lead->getKey(),
                'pharmacy_name' => $data['pharmacy_name'],
                'owner_name' => $data['owner_name'],
                'mobile' => $data['mobile'],
                'branch_count' => $data['branch_count'] ?? 1,
                'preferred_at' => $data['preferred_at'] ?? null,
                'message' => $data['message'] ?? null,
                'status' => DemoRequest::STATUS_PENDING,
            ]);

            $this->leads->addNote($lead, 'Demo request submitted from website.', type: LeadActivity::TYPE_DEMO);

            $this->notifier->notifyOperators(
                'demo.requested',
                'New demo request',
                "{$demo->pharmacy_name} requested a demo ({$demo->mobile}).",
                ['demo_id' => $demo->getKey(), 'lead_id' => $lead->getKey()],
                $demo->mobile,
            );

            return $demo;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DemoRequest $demo, array $data, User $actor): DemoRequest
    {
        return DB::transaction(function () use ($demo, $data, $actor) {
            $previous = $demo->status;

            if (isset($data['status']) && in_array($data['status'], [DemoRequest::STATUS_APPROVED, DemoRequest::STATUS_REJECTED], true)) {
                $data['reviewed_by'] = $actor->getKey();
                $data['reviewed_at'] = now();
            }

            $demo->fill($data);
            $demo->save();

            if ($demo->lead_id && isset($data['status']) && $data['status'] !== $previous) {
                $lead = $demo->lead;
                if ($lead) {
                    $leadStatus = match ($data['status']) {
                        DemoRequest::STATUS_SCHEDULED, DemoRequest::STATUS_APPROVED => Lead::STATUS_DEMO_SCHEDULED,
                        DemoRequest::STATUS_COMPLETED => Lead::STATUS_DEMO_COMPLETED,
                        DemoRequest::STATUS_REJECTED, DemoRequest::STATUS_CANCELLED => $lead->status,
                        default => $lead->status,
                    };

                    if ($leadStatus !== $lead->status) {
                        $this->leads->update($lead, ['status' => $leadStatus], $actor);
                    }

                    $this->leads->addNote(
                        $lead,
                        "Demo status updated to {$data['status']}.",
                        $actor,
                        LeadActivity::TYPE_DEMO,
                    );
                }
            }

            return $demo->fresh(['assignee', 'lead', 'reviewer']);
        });
    }

    /**
     * @return array<string, int>
     */
    public function dashboardStats(): array
    {
        return [
            'total' => DemoRequest::query()->count(),
            'pending' => DemoRequest::query()->where('status', DemoRequest::STATUS_PENDING)->count(),
            'scheduled' => DemoRequest::query()->where('status', DemoRequest::STATUS_SCHEDULED)->count(),
            'completed' => DemoRequest::query()->where('status', DemoRequest::STATUS_COMPLETED)->count(),
            'upcoming' => DemoRequest::query()
                ->where('status', DemoRequest::STATUS_SCHEDULED)
                ->whereBetween('scheduled_at', [now(), now()->addDays(7)])
                ->count(),
        ];
    }
}
