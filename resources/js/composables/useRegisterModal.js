import { ref } from 'vue';

const isOpen = ref(false);

export function useRegisterModal() {
    function openRegisterModal() {
        isOpen.value = true;
    }

    function closeRegisterModal() {
        isOpen.value = false;
    }

    return {
        isOpen,
        openRegisterModal,
        closeRegisterModal,
    };
}
