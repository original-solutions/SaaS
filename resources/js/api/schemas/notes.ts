import { z } from 'zod';

export const createNoteSchema = z.object({
  body: z.string().trim().min(1, 'Note is required.'),
});

export type CreateNoteValues = z.infer<typeof createNoteSchema>;
