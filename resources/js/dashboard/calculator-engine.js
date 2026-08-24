/* ================================
   resources/js/dashboard/calculator-engine.js
   Pure calculation logic for the Math AI Assistant's Calculator tab —
   no DOM access here, so math-panel.js can stay focused on wiring UI
   and this file can be reasoned about (and eyeballed for correctness)
   in isolation.

   math.js (mathjs/number, the ~lightweight numeric-only build) is
   loaded lazily via loadMathEngine() — call it once, on first
   Calculator-tab open, never at module import time.
   ================================ */

const FRACTION_MAX_DENOMINATOR = 9999;
const FRACTION_ROUNDTRIP_TOLERANCE = 1e-10;
const DISPLAY_DECIMALS = 10;

let mathEnginePromise = null;

/**
 * Lazily loads and configures the math.js evaluation engine.
 * Safe to call multiple times — the underlying dynamic import()
 * and math.create() only ever run once, subsequent calls reuse
 * the same promise/instance.
 *
 * @returns {Promise<object>} the configured mathjs instance
 */
export function loadMathEngine() {
    if (!mathEnginePromise) {
        mathEnginePromise = import('mathjs/number').then(({ create, all }) => {
            return create(all, {});
        });
    }

    return mathEnginePromise;
}

/**
 * Builds a math.js evaluation scope whose trig functions accept/return
 * degrees instead of radians, without touching the expression string
 * (string rewriting breaks on nested calls like sin(x+cos(y))).
 *
 * @param {object} math a configured mathjs instance
 * @param {'deg'|'rad'} angleMode
 * @returns {object} scope object to pass as evaluate()'s second argument
 */
export function buildScope(math, angleMode) {
    if (angleMode !== 'deg') {
        return {};
    }

    const toRad = (degrees) => (degrees * Math.PI) / 180;
    const toDeg = (radians) => (radians * 180) / Math.PI;

    return {
        sin: (x) => Math.sin(toRad(x)),
        cos: (x) => Math.cos(toRad(x)),
        tan: (x) => Math.tan(toRad(x)),
        asin: (x) => toDeg(Math.asin(x)),
        acos: (x) => toDeg(Math.acos(x)),
        atan: (x) => toDeg(Math.atan(x)),
    };
}

/**
 * Evaluates a calculator expression string, returning either a
 * successful numeric result or a short, student-friendly error —
 * never a raw exception.
 *
 * @param {object} math a configured mathjs instance
 * @param {string} expression
 * @param {'deg'|'rad'} angleMode
 * @returns {{ ok: true, value: number } | { ok: false, message: string }}
 */
export function evaluateExpression(math, expression, angleMode) {
    const trimmed = expression.trim();

    if (!trimmed) {
        return { ok: false, message: '' };
    }

    try {
        const scope = buildScope(math, angleMode);
        const result = math.evaluate(trimmed, scope);

        if (typeof result !== 'number' || !Number.isFinite(result)) {
            return { ok: false, message: 'Check your expression' };
        }

        return { ok: true, value: result };
    } catch {
        return { ok: false, message: 'Check your expression' };
    }
}

/**
 * Rounds a float to a fixed number of decimals and strips trailing
 * zeros/floating-point artifacts (e.g. 0.30000000000000004 -> 0.3).
 *
 * @param {number} value
 * @returns {number}
 */
export function roundDisplay(value) {
    return Number(value.toFixed(DISPLAY_DECIMALS));
}

/**
 * Converts a decimal value into a reduced fraction {numerator,
 * denominator, sign} using a continued-fraction expansion, guarding
 * against irrational/near-irrational values: if no denominator up to
 * FRACTION_MAX_DENOMINATOR round-trips within tolerance, returns null
 * so the caller falls back to decimal display (e.g. sqrt(2)).
 *
 * @param {number} value
 * @returns {{ numerator: number, denominator: number, negative: boolean } | null}
 */
export function toFraction(value) {
    if (!Number.isFinite(value)) {
        return null;
    }

    if (value === 0) {
        return { numerator: 0, denominator: 1, negative: false };
    }

    const negative = value < 0;
    const absolute = Math.abs(value);

    let previousNumerator = 1;
    let previousDenominator = 0;
    let numerator = Math.floor(absolute);
    let denominator = 1;
    let remainder = absolute - numerator;

    for (let iteration = 0; iteration < 30 && Math.abs(remainder) > Number.EPSILON; iteration++) {
        const inverted = 1 / remainder;
        const wholePart = Math.floor(inverted);

        const nextNumerator = wholePart * numerator + previousNumerator;
        const nextDenominator = wholePart * denominator + previousDenominator;

        if (nextDenominator > FRACTION_MAX_DENOMINATOR) {
            break;
        }

        previousNumerator = numerator;
        previousDenominator = denominator;
        numerator = nextNumerator;
        denominator = nextDenominator;

        remainder = inverted - wholePart;
    }

    if (Math.abs(numerator / denominator - absolute) > FRACTION_ROUNDTRIP_TOLERANCE) {
        return null;
    }

    return { numerator, denominator, negative };
}

/**
 * Formats a fraction as a display string — a whole number when the
 * denominator reduces to 1, a mixed number for improper fractions
 * (7/2 -> "3 1/2"), or a plain a/b otherwise.
 *
 * @param {{ numerator: number, denominator: number, negative: boolean }} fraction
 * @returns {string}
 */
export function formatFraction(fraction) {
    const { numerator, denominator, negative } = fraction;
    const sign = negative ? '-' : '';

    if (denominator === 1) {
        return `${sign}${numerator}`;
    }

    if (numerator > denominator) {
        const whole = Math.floor(numerator / denominator);
        const remainder = numerator % denominator;

        return remainder === 0
            ? `${sign}${whole}`
            : `${sign}${whole} ${remainder}/${denominator}`;
    }

    return `${sign}${numerator}/${denominator}`;
}

/**
 * Formats a numeric result for display in the given mode, falling
 * back to decimal when a fraction representation isn't available or
 * isn't requested.
 *
 * @param {number} value
 * @param {'fraction'|'decimal'} mode
 * @returns {string}
 */
export function formatResult(value, mode) {
    const rounded = roundDisplay(value);

    if (mode === 'fraction') {
        const fraction = toFraction(rounded);

        if (fraction) {
            return formatFraction(fraction);
        }
    }

    return String(rounded);
}
