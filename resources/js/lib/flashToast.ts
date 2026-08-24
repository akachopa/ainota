import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash as
            | (Record<string, unknown> & {
                  toast?: FlashToast;
                  success?: string;
                  error?: string;
              })
            | undefined;
        const data = flash?.toast;

        if (data?.type && data.message) {
            toast[data.type](data.message);
        }

        if (typeof flash?.success === 'string' && flash.success) {
            toast.success(flash.success);
        }

        if (typeof flash?.error === 'string' && flash.error) {
            toast.error(flash.error);
        }
    });
}
