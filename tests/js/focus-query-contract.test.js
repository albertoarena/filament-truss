// The focus deep link is a query parameter and nothing else: this package
// writes `?focus=<table>` into a Filament page URL and Truss's own frontend
// reads it on load. Nothing of ours runs in between, which is what makes the
// feature cheap and also what makes it silent when it breaks.
//
// So the parameter name is the contract, and it is Truss's to change. Read it
// out of the installed package rather than restating it, the same trick
// PaletteTokensTest uses on the private palette map: an upstream rename fails
// here, on the upgrade, instead of shipping a button that opens an unfocused
// diagram.
import { describe, expect, it } from 'vitest';

import { buildQuery, parseQuery } from '../../vendor/albertoarena/laravel-truss/resources/js/url-state.js';

// The one string this package hard-codes on the PHP side, in ViewInSchemaAction.
const PARAM = 'focus';

describe('the focus deep link contract', () => {
  it('is read from the query string Truss parses on load', () => {
    expect(parseQuery(`?${PARAM}=books`).focus).toBe('books');
  });

  it('is the name Truss itself writes back', () => {
    expect(buildQuery({ focus: 'books' })).toBe(`?${PARAM}=books`);
  });

  it('leaves the view unfocused when the parameter is absent', () => {
    expect(parseQuery('?filter=book').focus).toBe('');
  });
});
