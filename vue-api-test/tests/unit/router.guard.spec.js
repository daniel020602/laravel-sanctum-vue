import { describe, it, expect, vi } from 'vitest'

describe('authGuard exported from router/index', () => {
  afterEach(() => {
    vi.resetModules()
    vi.restoreAllMocks()
  })

  it('returns home when user present and route is guest', async () => {
  vi.resetModules()
  await vi.doMock('@/stores/auth.js', () => ({ useAuthStore: () => ({ getUser: async () => {}, user: { id: 1, is_admin: false } }) }))
  await vi.doMock('../stores/auth.js', () => ({ useAuthStore: () => ({ getUser: async () => {}, user: { id: 1, is_admin: false } }) }))

  const { authGuard } = await import('@/router/index')
  const res = await authGuard({ meta: { guest: true } }, {})
  expect(res).toEqual({ name: 'home' })
  })

  it('returns login when no user and route requires auth', async () => {
  vi.resetModules()
  await vi.doMock('@/stores/auth.js', () => ({ useAuthStore: () => ({ getUser: async () => {}, user: null }) }))
  await vi.doMock('../stores/auth.js', () => ({ useAuthStore: () => ({ getUser: async () => {}, user: null }) }))

  const { authGuard } = await import('@/router/index')
  const res = await authGuard({ meta: { auth: true } }, {})
  expect(res).toEqual({ name: 'login' })
  })

  it('returns home for non-admin user to admin routes, and undefined for admin', async () => {
  vi.resetModules()
  await vi.doMock('@/stores/auth.js', () => ({ useAuthStore: () => ({ getUser: async () => {}, user: { id: 2, is_admin: false } }) }))
  await vi.doMock('../stores/auth.js', () => ({ useAuthStore: () => ({ getUser: async () => {}, user: { id: 2, is_admin: false } }) }))
  let { authGuard } = await import('@/router/index')
  let res = await authGuard({ meta: { requiresAdmin: true } }, {})
  expect(res).toEqual({ name: 'home' })

  // admin case
  vi.resetModules()
  await vi.doMock('@/stores/auth.js', () => ({ useAuthStore: () => ({ getUser: async () => {}, user: { id: 3, is_admin: true } }) }))
  await vi.doMock('../stores/auth.js', () => ({ useAuthStore: () => ({ getUser: async () => {}, user: { id: 3, is_admin: true } }) }))
  ;({ authGuard } = await import('@/router/index'))
  res = await authGuard({ meta: { requiresAdmin: true } }, {})
  expect(res).toBeUndefined()
  })
})
