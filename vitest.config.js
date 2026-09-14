import { defineConfig } from 'vitest/config';

// Vitest owns the client-side unit tests under tests/js. They are kept out of
// the Pest suite's way by extension alone: phpunit.xml scans the same `tests`
// directory but only collects *Test.php.
//
// The default environment is node, because most of these tests read a shipped
// file and assert on its contents. The one test that needs a DOM asks for it
// with a `@vitest-environment jsdom` annotation at the top of the file, so the
// rest of the suite does not pay for a document it never touches.
export default defineConfig({
  test: {
    include: ['tests/js/**/*.test.js'],
  },
});
