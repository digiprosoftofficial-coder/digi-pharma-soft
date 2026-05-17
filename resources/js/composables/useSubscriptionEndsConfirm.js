import { oneYearFromToday } from '@/utils/dates';
import { ref } from 'vue';

/**
 * Confirm subscription end date changes; keep trial/plan updates from wiping a custom date.
 */
export function useSubscriptionEndsConfirm(form, t, initialEnds) {
    const committedSubscriptionEnds = ref(initialEnds ?? oneYearFromToday());
    const subscriptionCustomized = ref(false);
    const subscriptionInputKey = ref(0);

    function syncSubscriptionInput(value) {
        form.subscription_ends_at = value;
        subscriptionInputKey.value += 1;
    }

    function setDefaultSubscriptionEnds() {
        const value = oneYearFromToday();
        committedSubscriptionEnds.value = value;
        syncSubscriptionInput(value);
        subscriptionCustomized.value = false;
    }

    function onSubscriptionEndsChange(event) {
        const newValue = event.target.value;
        if (!newValue || newValue === committedSubscriptionEnds.value) {
            return;
        }
        if (confirm(t('platform.subscription_ends_change_confirm'))) {
            committedSubscriptionEnds.value = newValue;
            form.subscription_ends_at = newValue;
            subscriptionCustomized.value = true;
            return;
        }
        syncSubscriptionInput(committedSubscriptionEnds.value);
    }

    /** Update trial from plan; reset subscription only when admin has not customized it. */
    function applyTrialFromPlan(trialEndsAt) {
        form.trial_ends_at = trialEndsAt;
        if (!subscriptionCustomized.value) {
            setDefaultSubscriptionEnds();
        }
    }

    return {
        committedSubscriptionEnds,
        subscriptionCustomized,
        subscriptionInputKey,
        onSubscriptionEndsChange,
        setDefaultSubscriptionEnds,
        applyTrialFromPlan,
    };
}
