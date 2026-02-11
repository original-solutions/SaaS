import { z } from 'zod';
import { emailSchema } from '@/api/schemas/auth';

export const profileSchema = z.object({
    name: z.string().trim().min(1, 'Name is required.'),
});

export type ProfileValues = z.infer<typeof profileSchema>;

export const changePasswordSchema = z
    .object({
        current_password: z.string().min(1, 'Current password is required.'),
        password: z.string().min(8, 'Password must be at least 8 characters.'),
        password_confirmation: z.string().min(1, 'Please confirm your password.'),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Passwords do not match.',
        path: ['password_confirmation'],
    });

export type ChangePasswordValues = z.infer<typeof changePasswordSchema>;

export const changeEmailSchema = z.object({
    email: emailSchema,
    password: z.string().min(1, 'Current password is required.'),
});

export type ChangeEmailValues = z.infer<typeof changeEmailSchema>;

export const deleteAccountSchema = z.object({
    password: z.string().min(1, 'Password is required.'),
});

export type DeleteAccountValues = z.infer<typeof deleteAccountSchema>;

export const createPersonalAccessTokenSchema = z.object({
    name: z
        .string()
        .trim()
        .min(1, 'Token name is required.')
        .max(255, 'Token name must be 255 characters or fewer.'),
});

export type CreatePersonalAccessTokenValues = z.infer<typeof createPersonalAccessTokenSchema>;

export const twoFactorConfirmSchema = z.object({
    code: z
        .string()
        .trim()
        .regex(/^\d{6}$/, 'Enter a 6-digit code.'),
});

export type TwoFactorConfirmValues = z.infer<typeof twoFactorConfirmSchema>;

export const twoFactorDisableSchema = z.object({
    password: z.string().min(1, 'Password is required.'),
});

export type TwoFactorDisableValues = z.infer<typeof twoFactorDisableSchema>;
