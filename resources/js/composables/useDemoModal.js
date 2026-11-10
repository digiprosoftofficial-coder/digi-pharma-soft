import { ref } from 'vue';

const isOpen = ref(false);

export function useDemoModal() {
    function openDemoModal() {
        isOpen.value = true;
    }

    function closeDemoModal() {
        isOpen.value = false;
    }

    return {
        isOpen,
        openDemoModal,
        closeDemoModal,
    };
}
