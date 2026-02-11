import { z } from 'zod';

export const saveViewSchema = z.object({
    name: z
        .string()
        .trim()
        .min(1, 'View name is required.')
        .max(255, 'View name must be 255 characters or fewer.'),
});

export type SaveViewValues = z.infer<typeof saveViewSchema>;
