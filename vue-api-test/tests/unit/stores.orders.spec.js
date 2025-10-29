import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

describe('orders store actions', () => {
  beforeEach(() => {
    // fresh pinia per test
    setActivePinia(createPinia())

    // stub localStorage.getItem used for Authorization header
    globalThis.localStorage = { getItem: vi.fn(() => 'fake-token') }
  })

  afterEach(() => {
    vi.restoreAllMocks()
    // reset module registry so store state is isolated between tests
    vi.resetModules()
  })

  it('fetchOrders populates store.orders with API response', async () => {
    const ordersData = [{ id: 1, item: 'A' }, { id: 2, item: 'B' }]

    vi.stubGlobal('fetch', vi.fn(() => Promise.resolve({ ok: true, json: () => Promise.resolve(ordersData) })))

    const { useOrdersStore } = await import('@/stores/orders')
    const store = useOrdersStore()

    const result = await store.fetchOrders()

    expect(result).toEqual(ordersData)
    expect(store.orders).toEqual(ordersData)
    expect(globalThis.fetch).toHaveBeenCalled()
  })

  it('createOrder returns errors when API responds with validation errors', async () => {
    const serverPayload = { errors: { email: ['invalid'] } }

    vi.stubGlobal('fetch', vi.fn(() => Promise.resolve({ ok: false, json: () => Promise.resolve(serverPayload) })))

    const { useOrdersStore } = await import('@/stores/orders')
    const store = useOrdersStore()

    const res = await store.createOrder({ foo: 'bar' })

    expect(res).toHaveProperty('errors')
    expect(res.errors).toEqual(serverPayload.errors)
    expect(store.orders).toEqual([])
  })

  it('createOrder appends and sets current on success', async () => {
    const newOrder = { id: 5, item: 'New' }
    vi.stubGlobal('fetch', vi.fn(() => Promise.resolve({ ok: true, json: () => Promise.resolve(newOrder) })))

    const { useOrdersStore } = await import('@/stores/orders')
    const store = useOrdersStore()

    const created = await store.createOrder({ item: 'New' })

    expect(created).toEqual(newOrder)
    expect(store.orders).toContainEqual(newOrder)
    expect(store.current).toEqual(newOrder)
  })

  it('markAsPaid updates order in list and current when present', async () => {
    const original = { id: 10, is_paid: false }
    const updated = { id: 10, is_paid: true }

    // prepopulate store by importing and setting state
    const { useOrdersStore } = await import('@/stores/orders')
    const store = useOrdersStore()
    store.orders = [original]
    store.current = original

    vi.stubGlobal('fetch', vi.fn(() => Promise.resolve({ ok: true, json: () => Promise.resolve(updated) })))

    const result = await store.markAsPaid(10)

    expect(result).toEqual(updated)
    expect(store.orders.find(o => o.id === 10).is_paid).toBe(true)
    expect(store.current.is_paid).toBe(true)
  })
})
