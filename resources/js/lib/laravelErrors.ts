export type LaravelErrors = Record<string, string[] | string | undefined>;

export function mapLaravelErrors(errors: LaravelErrors | undefined): Record<string, string> {
  if (!errors) {
    return {};
  }

  return Object.fromEntries(
    Object.entries(errors)
      .filter(([, value]) => value !== undefined)
      .map(([key, value]) => {
        if (Array.isArray(value)) {
          return [key, value[0] ?? ''];
        }

        return [key, value ?? ''];
      })
  );
}
