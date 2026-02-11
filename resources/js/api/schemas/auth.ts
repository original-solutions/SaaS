import { z } from 'zod';

export const emailSchema = z
    .string()
    .trim()
    .min(1, 'Email is required.')
    .email('Please enter a valid email.');

export const loginSchema = z.object({
    email: emailSchema,
    password: z.string().min(1, 'Password is required.'),
});

export type LoginValues = z.infer<typeof loginSchema>;

export const forgotPasswordSchema = z.object({
    email: emailSchema,
});

export type ForgotPasswordValues = z.infer<typeof forgotPasswordSchema>;

export const magicLinkSchema = z.object({
    email: emailSchema,
});

export type MagicLinkValues = z.infer<typeof magicLinkSchema>;

export const resetPasswordSchema = z
    .object({
        email: emailSchema,
        password: z.string().min(8, 'Password must be at least 8 characters.'),
        password_confirmation: z.string().min(1, 'Please confirm your password.'),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Passwords do not match.',
        path: ['password_confirmation'],
    });

export type ResetPasswordValues = z.infer<typeof resetPasswordSchema>;

export const twoFactorCodeSchema = z.object({
    code: z
        .string()
        .trim()
        .regex(/^\d{6}$/, 'Enter a 6-digit code.'),
});

export type TwoFactorCodeValues = z.infer<typeof twoFactorCodeSchema>;

export const twoFactorRecoverySchema = z.object({
    recovery_code: z.string().trim().min(1, 'Recovery code is required.'),
});

export type TwoFactorRecoveryValues = z.infer<typeof twoFactorRecoverySchema>;
