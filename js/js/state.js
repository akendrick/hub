// state.js — all shared mutable state for kaslo-weather
// Loaded FIRST (before any other JS module) so every file can read/write these.
// Never declare these variables in any other file.

let pressureLog   = [];      // rolling pressure readings for trend calc
let forecastData  = null;    // latest Open-Meteo response
let forecastDates = [];      // ['YYYY-MM-DD', …] for current 21-day window
let todoRawItems  = [];      // full unfiltered todo list (used by calendar)
let doneSet       = new Set(); // locally-toggled done IDs (populated by todo.js)
