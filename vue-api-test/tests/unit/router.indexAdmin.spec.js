import { describe, it, expect } from 'vitest'

describe('adminRoutes (via merged router)', () => {
  it('router contains expected admin route names and those routes require admin', async () => {
    const router = (await import('@/router/index')).default
    const find = name => router.getRoutes().find(r => r.name === name)

    const admin = find('admin')
    const adminOrders = find('admin-orders')
    const adminStats = find('admin-order-statistics')

    expect(admin).toBeDefined()
    expect(adminOrders).toBeDefined()
    expect(adminStats).toBeDefined()

    expect(admin && admin.meta && admin.meta.requiresAdmin).toBe(true)
    expect(admin && admin.meta && admin.meta.auth).toBe(true)
    expect(adminOrders && adminOrders.meta && adminOrders.meta.requiresAdmin).toBe(true)
    expect(adminOrders && adminOrders.meta && adminOrders.meta.auth).toBe(true)
    expect(adminStats && adminStats.meta && adminStats.meta.requiresAdmin).toBe(true)
    expect(adminStats && adminStats.meta && adminStats.meta.auth).toBe(true)
  })
})
