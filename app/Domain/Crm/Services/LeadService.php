<?php

namespace App\Domain\Crm\Services;

use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Models\LeadActivity;
use App\Models\User;
use App\Support\Platform\PlatformEventNotifier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class LeadService
{
    public function __construct(private readonly PlatformEventNotifier $notifier) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Lead::query()
            ->with(['assignee:id,name,email', 'interestedPlan:id,name,slug', 'creator:id,name'])
            ->orderByDesc('updated_at');

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('pharmacy_name', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', (int) $filters['assigned_to']);
        }

        if (! empty($filters['follow_up'])) {
            if ($filters['follow_up'] === 'overdue') {
                $query->whereNotNull('follow_up_at')->where('follow_up_at', '<', now())
                    ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_LOST]);
            } elseif ($filters['follow_up'] === 'upcoming') {
                $query->whereBetween('follow_up_at', [now(), now()->addDays(7)]);
            }
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): Lead
    {
        return DB::transaction(function () use ($data, $actor) {
            $lead = Lead::query()->create([
                ...$data,
                'status' => $data['status'] ?? Lead::STATUS_NEW,
                'created_by' => $actor?->getKey(),
            ]);

            $this->recordActivity($lead, LeadActivity::TYPE_NOTE, $actor, body: 'Lead created.');

            $this->notifier->notifyOperators(
                'lead.created',
                'New lead',
                "{$lead->pharmacy_name} ({$lead->phone})",
                ['lead_id' => $lead->getKey()],
                $lead->whatsapp ?: $lead->phone,
            );

            return $lead->fresh(['assignee', 'interestedPlan']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Lead $lead, array $data, ?User $actor = null): Lead
    {
        return DB::transaction(function () use ($lead, $data, $actor) {
            $fromStatus = $lead->status;
            $fromAssignee = $lead->assigned_to;

            $lead->fill($data);
            $lead->save();

            if (isset($data['status']) && $data['status'] !== $fromStatus) {
                $this->recordActivity(
                    $lead,
                    LeadActivity::TYPE_STATUS_CHANGE,
                    $actor,
                    fromStatus: $fromStatus,
                    toStatus: $data['status'],
                    body: "Status changed from {$fromStatus} to {$data['status']}.",
                );
            }

            if (array_key_exists('assigned_to', $data) && (int) $data['assigned_to'] !== (int) $fromAssignee) {
                $this->recordActivity(
                    $lead,
                    LeadActivity::TYPE_ASSIGNMENT,
                    $actor,
                    body: 'Lead assignment updated.',
                    meta: ['from' => $fromAssignee, 'to' => $data['assigned_to']],
                );
            }

            if (array_key_exists('follow_up_at', $data)) {
                $this->recordActivity(
                    $lead,
                    LeadActivity::TYPE_FOLLOW_UP,
                    $actor,
                    body: 'Follow-up date updated.',
                    meta: ['follow_up_at' => $lead->follow_up_at?->toIso8601String()],
                );
            }

            if (! empty($data['notes']) && ($data['notes'] !== $lead->getOriginal('notes'))) {
                // notes already saved via fill
            }

            return $lead->fresh(['assignee', 'interestedPlan', 'activities.user']);
        });
    }

    public function addNote(Lead $lead, string $body, ?User $actor = null, string $type = LeadActivity::TYPE_NOTE): LeadActivity
    {
        return $this->recordActivity($lead, $type, $actor, body: $body);
    }

    public function assign(Lead $lead, ?int $userId, ?User $actor = null): Lead
    {
        return $this->update($lead, ['assigned_to' => $userId], $actor);
    }

    /**
     * @return array<string, int>
     */
    public function dashboardStats(): array
    {
        $base = Lead::query();

        return [
            'total' => (clone $base)->count(),
            'new' => (clone $base)->where('status', Lead::STATUS_NEW)->count(),
            'contacted' => (clone $base)->where('status', Lead::STATUS_CONTACTED)->count(),
            'demo_scheduled' => (clone $base)->where('status', Lead::STATUS_DEMO_SCHEDULED)->count(),
            'trial_started' => (clone $base)->where('status', Lead::STATUS_TRIAL_STARTED)->count(),
            'converted' => (clone $base)->where('status', Lead::STATUS_CONVERTED)->count(),
            'lost' => (clone $base)->where('status', Lead::STATUS_LOST)->count(),
            'follow_ups_due' => (clone $base)
                ->whereNotNull('follow_up_at')
                ->where('follow_up_at', '<=', now()->addDay())
                ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_LOST])
                ->count(),
        ];
    }

    public function conversionRate(): float
    {
        $total = Lead::query()->count();
        if ($total === 0) {
            return 0.0;
        }

        $converted = Lead::query()->where('status', Lead::STATUS_CONVERTED)->count();

        return round(($converted / $total) * 100, 1);
    }

    private function recordActivity(
        Lead $lead,
        string $type,
        ?User $actor = null,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $body = null,
        ?array $meta = null,
    ): LeadActivity {
        return LeadActivity::query()->create([
            'lead_id' => $lead->getKey(),
            'user_id' => $actor?->getKey(),
            'type' => $type,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'body' => $body,
            'meta' => $meta,
        ]);
    }
}
