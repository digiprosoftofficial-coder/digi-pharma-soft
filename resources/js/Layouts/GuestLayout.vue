<template>
    <div class="min-vh-100 d-flex flex-column justify-content-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <slot />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useTheme } from '@/composables/useTheme';

useTheme();
</script>
