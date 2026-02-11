import { reactive, readonly } from 'vue';

export type ConfirmDialogOptions = {
  title: string;
  description?: string;
  confirmText?: string;
  cancelText?: string;
  destructive?: boolean;
};

type ConfirmDialogState = {
  open: boolean;
  options: ConfirmDialogOptions | null;
  resolve: ((result: boolean) => void) | null;
};

const state = reactive<ConfirmDialogState>({
  open: false,
  options: null,
  resolve: null,
});

export function confirmDialog(options: ConfirmDialogOptions): Promise<boolean> {
  if (state.open && state.resolve) {
    state.resolve(false);
  }

  state.open = true;
  state.options = options;

  return new Promise((resolve) => {
    state.resolve = resolve;
  });
}

export function useConfirmDialog() {
  function close(result: boolean): void {
    if (state.resolve) {
      state.resolve(result);
    }

    state.open = false;
    state.options = null;
    state.resolve = null;
  }

  return {
    state: readonly(state),
    close,
  };
}
