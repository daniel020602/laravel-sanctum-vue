import { describe } from 'vitest'

// Skipped: brittle navigation-based beforeEach tests. The guard logic is
// covered directly in router.guard.spec.js which imports and calls the
// exported authGuard function. Keep this file present but skipped so it
// doesn't interfere with test runs.
describe.skip('router beforeEach guard (skipped)', () => {})
