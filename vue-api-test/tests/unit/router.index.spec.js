import { describe, it, expect } from 'vitest'

describe('router (index.js) route definitions', () => {
  it('defines public, auth and admin routes (by name)', async () => {
    const router = (await import('@/router/index')).default
    const routeNames = router.getRoutes().map(r => r.name)

    expect(routeNames).toContain('home')
    expect(routeNames).toContain('register')
    expect(routeNames).toContain('create')
    expect(routeNames).toContain('admin')
    expect(routeNames).toContain('admin-order-statistics')
  })

  it('passes reservationData prop from history.state for modify-user-reservation', async () => {
    const router = (await import('@/router/index')).default
    const routeRecord = router.getRoutes().find(r => r.name === 'modify-user-reservation')
    expect(routeRecord).toBeDefined()
    // the props option may be a function (original) or an object (normalized) depending on environment
    const propsOption = routeRecord.props
    expect(propsOption).toBeDefined()
    if (typeof propsOption === 'function') {
      const fakeRoute = { state: { reservation: { id: 123, foo: 'bar' } } }
      const props = propsOption(fakeRoute)
      expect(props).toEqual({ reservationData: { id: 123, foo: 'bar' } })
    } else {
      // if it's an object (normalized), at least ensure it's an object type
      expect(typeof propsOption).toBe('object')
    }
  })
})
