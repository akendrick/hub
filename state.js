// state.js — shared mutable state
// Uses window property assignment (not let/const/var) so this file is
// safe to coexist with any stale cached file that declares the same names.
// Loaded first; all other modules read/write these as plain globals.

window.pressureLog   = [];        // rolling pressure readings for trend
window.forecastData  = null;      // latest Open-Meteo response object
window.forecastDates = [];        // ['YYYY-MM-DD', …] for 21-day window
window.todoRawItems  = [];        // full unfiltered todo list
window.doneSet       = new Set(); // locally-toggled done IDs
