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

function displayLocale() {
    if (typeof document !== 'undefined' && document.documentElement.lang) {
        return document.documentElement.lang;
    }

    if (typeof navigator !== 'undefined' && navigator.language) {
        return navigator.language;
    }

    return 'en';
}

function normalizeIso(value) {
    return String(value).replace(/\.(\d{3})\d+(Z|[+-])/, '.$1$2');
}

function dateFromCalendarPart(value) {
    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!match) {
        return null;
    }

    return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
}

export function formatHumanDate(value, fallback = '—') {
    if (!value) {
        return fallback;
    }

    const date = dateFromCalendarPart(value) ?? new Date(normalizeIso(value));
    if (Number.isNaN(date.getTime())) {
        return String(value).slice(0, 10) || fallback;
    }

    return new Intl.DateTimeFormat(displayLocale(), {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(date);
}

export function formatHumanDateTime(value, fallback = '—') {
    if (!value) {
        return fallback;
    }

    const date = new Date(normalizeIso(value));
    if (Number.isNaN(date.getTime())) {
        return formatHumanDate(value, fallback);
    }

    return new Intl.DateTimeFormat(displayLocale(), {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(date);
}
