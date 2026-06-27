import { formatHumanDate } from '@/utils/dates';

export function batchesWithStock(item) {
    const batches = Array.isArray(item?.batches) ? item.batches : (item?.batches?.data ?? []);

    return batches.filter(
        (b) => Number(b.quantity_on_hand ?? 0) > 0 && !b.is_expired,
    );
}

export function formatBatchLabel(batch) {
    if (!batch) {
        return '';
    }
    const no = batch.batch_no ?? '—';
    const exp = batch.expiry_date ? ` · exp ${formatHumanDate(batch.expiry_date)}` : '';

    return `${no}${exp}`;
}

export function totalBaseStock(batches) {
    return (batches ?? []).reduce((sum, b) => sum + Number(b.quantity_on_hand ?? 0), 0);
}

export function onBatchChange(line) {
    const batch = line.batches?.find((b) => b.id === line.product_batch_id);
    if (!batch) {
        return;
    }
    line.batch_stock = Number(batch.quantity_on_hand ?? 0);
    line.batch_no = batch.batch_no;
    line.expiry_date = batch.expiry_date;
}
