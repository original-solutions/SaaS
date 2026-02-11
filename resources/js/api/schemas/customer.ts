import { z } from 'zod';

export const customerSchema = z.object({
  name: z.string().trim().min(1, 'Name is required.').max(255, 'Name is too long.'),
  email: z.string().max(255, 'Email is too long.'),
  phone: z.string().max(255, 'Phone is too long.'),
  company: z.string().max(255, 'Company is too long.'),
  status: z.enum(['active', 'inactive']),
});

export type CustomerFormValues = z.infer<typeof customerSchema>;
