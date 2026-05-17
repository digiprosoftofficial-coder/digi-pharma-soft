/** Format a Date as YYYY-MM-DD in the user's local timezone (for <input type="date">). */
export function formatDateInput(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

export function addDays(from, days) {
    const date = new Date(from);
    date.setDate(date.getDate() + days);
    return date;
}

export function addYears(from, years) {
    const date = new Date(from);
    date.setFullYear(date.getFullYear() + years);
    return date;
}

/** Today (local) + 1 calendar year. */
export function oneYearFromToday() {
    return formatDateInput(addYears(new Date(), 1));
}

/** Parse ISO / date string for display in date inputs (local calendar day). */
export function isoToDateInput(iso) {
    if (!iso) {
        return '';
    }
    const parsed = new Date(iso);
    if (Number.isNaN(parsed.getTime())) {
        return String(iso).slice(0, 10);
    }
    return formatDateInput(parsed);
}
