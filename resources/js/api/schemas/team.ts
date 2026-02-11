import { z } from 'zod';
import { emailSchema } from '@/api/schemas/auth';

export const inviteMemberSchema = z.object({
    email: emailSchema.max(255, 'Email must be 255 characters or fewer.'),
    role: z.enum(['member', 'readonly'], {
        message: 'Please select a valid role.',
    }),
});

export type InviteMemberValues = z.infer<typeof inviteMemberSchema>;
