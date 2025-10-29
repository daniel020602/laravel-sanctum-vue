import { describe, it, expect } from 'vitest'

describe('authRoutes (via merged router)', () => {
  it('router contains expected auth route names and those routes have meta.auth true', async () => {
    const router = (await import('@/router/index')).default
    const find = name => router.getRoutes().find(r => r.name === name)

    const create = find('create')
    const userdata = find('userdata')
    const subscription = find('subscription')

    expect(create).toBeDefined()
    expect(userdata).toBeDefined()
    expect(subscription).toBeDefined()

    expect(create && create.meta && create.meta.auth).toBe(true)
    expect(userdata && userdata.meta && userdata.meta.auth).toBe(true)
    expect(subscription && subscription.meta && subscription.meta.auth).toBe(true)
  })
})
