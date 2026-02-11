import echo from './reverb';
import type { Channel, PresenceChannel } from 'laravel-echo';

/**
 * Subscribe to a private tenant channel for receiving entity updates.
 */
export function subscribeTenantChannel(tenantId: number): Channel {
    return echo.private(`tenant.${tenantId}`);
}

/**
 * Subscribe to a presence channel for a tenant (online indicators).
 */
export function subscribeTenantPresence(tenantId: number): PresenceChannel {
    return echo.join(`tenant.${tenantId}.presence`);
}

/**
 * Subscribe to a user's private channel for personal notifications.
 */
export function subscribeUserChannel(userId: number): Channel {
    return echo.private(`App.Models.User.${userId}`);
}

/**
 * Leave a private tenant channel.
 */
export function leaveTenantChannel(tenantId: number): void {
    echo.leave(`tenant.${tenantId}`);
}

/**
 * Leave a tenant presence channel.
 */
export function leaveTenantPresence(tenantId: number): void {
    echo.leave(`tenant.${tenantId}.presence`);
}

/**
 * Leave a user's private channel.
 */
export function leaveUserChannel(userId: number): void {
    echo.leave(`App.Models.User.${userId}`);
}
