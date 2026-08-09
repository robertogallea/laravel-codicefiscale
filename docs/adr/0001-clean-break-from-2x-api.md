# Clean break from the 2.x API for 3.0

2.x's `CodiceFiscale` mixes mutable parsing state, validation, and generation behind one class; 3.x replaces it with an immutable value object plus separate `Parser`/`Generator`/`Validator`/`Matcher` services. 3.0 makes no attempt to preserve or shim the 2.x API (`new CodiceFiscale()`, `->parse()`, `::generate()`, `->getError()`, `->getFirstName()`/`->getLastName()`) — migration is documented in `UPGRADE.md` only.

A stateful, mutable 2.x API cannot be honestly wrapped around an immutable value-object core without compromising the new design; a clear migration table is more honest than a shim that only half-works.
