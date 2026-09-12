/* ================================
   resources/js/dashboard/quiz-generators.js
   Deterministic (non-AI) Pretest/Posttest question generation for the
   12 fixed Grade 10 PH curriculum sub-topics.

   Why this exists: asking an AI to both invent a math question AND
   compute its correct answer was unreliable (~30-47% of answers were
   mathematically wrong in manual review, even after prompt tuning, a
   stronger model, and an AI "verify your own answer" pass — the same
   model checking its own work is prone to the same mistakes). These
   generators instead pick random problem parameters and compute the
   correct answer with real formulas, so correctness is guaranteed by
   construction. Teacher-added custom topics (not one of the 12 fixed
   keys here) still fall back to the AI pipeline in
   teacher_dashboard.js, since there is no formula to hand-write for an
   arbitrary topic name.
   ================================ */

/* ------------------------------------------------------------------
   Fraction — exact rational arithmetic (integer numerator/denominator
   with its own gcd-based reduce). Deliberately NOT the calculator's
   toFraction()/formatFraction() in calculator-engine.js — those round-
   trip a decimal through continued-fraction expansion, which is fine
   for calculator display but a floating-point-precision risk here,
   where this class IS the ground truth for a correct answer.
------------------------------------------------------------------ */
function gcd(a, b) {
    a = Math.abs(a);
    b = Math.abs(b);
    while (b) {
        [a, b] = [b, a % b];
    }
    return a || 1;
}

class Fraction {
    constructor(num, den = 1) {
        if (den === 0) {
            throw new Error('Fraction: division by zero');
        }
        if (den < 0) {
            num = -num;
            den = -den;
        }
        const g = gcd(num, den);
        this.num = num === 0 ? 0 : num / g;
        this.den = num === 0 ? 1 : den / g;
    }

    toDisplayString() {
        if (this.num === 0) {
            return '0';
        }
        if (this.den === 1) {
            return String(this.num);
        }
        // Improper fraction, sign on the numerator only — unambiguous
        // for string-equality distractor dedup, and the conventional
        // way Grade-10 PH answer keys present equation solutions.
        return `${this.num}/${this.den}`;
    }
}

/* ------------------------------------------------------------------
   Small random-selection helpers
------------------------------------------------------------------ */
function randInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function randIntExcept(min, max, exclude) {
    let value;
    let tries = 0;
    do {
        value = randInt(min, max);
        tries++;
    } while (value === exclude && tries < 200);
    return value;
}

function pickFrom(values) {
    return values[randInt(0, values.length - 1)];
}

function sampleDistinct(min, max, count) {
    const pool = [];
    for (let i = min; i <= max; i++) {
        pool.push(i);
    }
    for (let i = pool.length - 1; i > 0; i--) {
        const j = randInt(0, i);
        [pool[i], pool[j]] = [pool[j], pool[i]];
    }
    return pool.slice(0, count);
}

/* ------------------------------------------------------------------
   selectUniqueDistractors / assembleOptions — shared by every
   generator below AND by parseQuizArray() in teacher_dashboard.js
   (the AI-fed custom-topic path), so there is one source of truth for
   "pick 3 distinct wrong values" and "shuffle into A/B/C/D options".
------------------------------------------------------------------ */
export function selectUniqueDistractors(correctValue, candidates, count = 3) {
    const normalize = (v) => String(v ?? '').trim().toLowerCase();
    const seen = new Set([normalize(correctValue)]);
    const out = [];
    for (const candidate of candidates) {
        const key = normalize(candidate);
        if (key === '' || seen.has(key)) {
            continue;
        }
        seen.add(key);
        out.push(candidate);
        if (out.length === count) {
            break;
        }
    }
    return out;
}

export function assembleOptions(correctValue, distractors) {
    const values = [correctValue, ...distractors].sort(() => Math.random() - 0.5);
    const options = {};
    let answer = '';
    values.forEach((val, idx) => {
        const key = String.fromCharCode(65 + idx); // A-D
        options[key] = String(val);
        if (val === correctValue) {
            answer = key;
        }
    });
    return { options, answer };
}

/* ------------------------------------------------------------------
   Formatting helpers shared across the polynomial/exponential/log
   generators, to match buildHardcodedActivity()'s existing notation
   (aₙ, Sₙ, x², log₂, …).
------------------------------------------------------------------ */
function formatSignedTerm(coeff, term) {
    if (coeff === 0) {
        return '';
    }
    return `${coeff < 0 ? ' − ' : ' + '}${Math.abs(coeff)}${term}`;
}

const SUBSCRIPT_DIGITS = { 0: '₀', 1: '₁', 2: '₂', 3: '₃', 4: '₄', 5: '₅', 6: '₆', 7: '₇', 8: '₈', 9: '₉' };
function toSubscript(n) {
    return String(n).split('').map((ch) => SUBSCRIPT_DIGITS[ch] ?? ch).join('');
}

// Horner's method / synthetic division. coeffs = [c_n, ..., c1, c0],
// highest degree first. Shared by division_polynomials & remainder_theorem.
function syntheticDivide(coeffs, k) {
    const quotient = [coeffs[0]];
    for (let i = 1; i < coeffs.length - 1; i++) {
        quotient.push(coeffs[i] + k * quotient[i - 1]);
    }
    const remainder = coeffs[coeffs.length - 1] + k * quotient[quotient.length - 1];
    return { quotient, remainder };
}

/* ================================================================
   1. arithmetic_sequence — aₙ = a₁ + (n−1)d, Sₙ = n·a₁ + n(n−1)/2·d
   ================================================================ */
function genArithmeticSequence(difficulty) {
    const ranges = {
        easy: { a1: [1, 20], d: [1, 9], n: [4, 10] },
        medium: { a1: [-20, 20], d: [-12, 12], n: [8, 20] },
        hard: { a1: [-50, 50], d: [-20, 20], n: [15, 40] },
    }[difficulty];

    const a1 = randInt(ranges.a1[0], ranges.a1[1]);
    const d = randIntExcept(ranges.d[0], ranges.d[1], 0);
    const n = randInt(ranges.n[0], ranges.n[1]);

    if (Math.random() < 0.5) {
        const correct = a1 + (n - 1) * d;
        // Preview only 3 terms (n's minimum is 4 in every difficulty
        // tier) so the asked-for term is never one of the shown values.
        const question = `Find the ${n}th term of the arithmetic sequence: ${a1}, ${a1 + d}, ${a1 + 2 * d}, …`;
        const distractors = selectUniqueDistractors(String(correct), [
            String(a1 + n * d),
            String(a1 + (n - 2) * d),
            String(a1 * n + d),
            String(correct + d),
            String(correct - d),
        ]);
        const { options, answer } = assembleOptions(String(correct), distractors);
        return { question, options, answer };
    }

    const correct = n * a1 + (n * (n - 1) / 2) * d;
    const question = `Find the sum of the first ${n} terms of the arithmetic sequence: ${a1}, ${a1 + d}, ${a1 + 2 * d}, …`;
    const distractors = selectUniqueDistractors(String(correct), [
        String(n * (2 * a1 + (n - 1) * d)),
        String(Math.round((n / 2) * (2 * a1 + n * d))),
        String(a1 + (n - 1) * d),
        String(correct + d),
        String(correct - n),
    ]);
    const { options, answer } = assembleOptions(String(correct), distractors);
    return { question, options, answer };
}

/* ================================================================
   2. geometric_sequence — aₙ = a₁·rⁿ⁻¹, Sₙ = sum of first n terms
   ================================================================ */
function genGeometricSequence(difficulty) {
    const cfg = {
        easy: { a1: [1, 10], r: [2, 3], n: [4, 6] },
        medium: { a1: [-15, 15], r: [-3, -2, 2, 3], n: [4, 8] },
        hard: { a1: [-20, 20], r: [-4, -3, -2, 2, 3, 4], n: [5, 10] },
    }[difficulty];

    const a1 = difficulty === 'easy' ? randInt(cfg.a1[0], cfg.a1[1]) : randIntExcept(cfg.a1[0], cfg.a1[1], 0);
    const r = difficulty === 'easy' ? randInt(cfg.r[0], cfg.r[1]) : pickFrom(cfg.r);
    const n = randInt(cfg.n[0], cfg.n[1]);

    if (Math.random() < 0.5) {
        const correct = a1 * r ** (n - 1);
        // Preview only 3 terms (n's minimum is 4 in every difficulty
        // tier) so the asked-for term is never one of the shown values.
        const question = `Find the ${n}th term of the geometric sequence: ${a1}, ${a1 * r}, ${a1 * r ** 2}, …`;
        const distractors = selectUniqueDistractors(String(correct), [
            String(a1 * r ** n),
            String(a1 * (n - 1) * r),
            String(a1 * r ** (n - 2)),
            String(correct * r),
            String(Math.round(correct / r)),
        ]);
        const { options, answer } = assembleOptions(String(correct), distractors);
        return { question, options, answer };
    }

    const terms = [];
    let t = a1;
    for (let i = 0; i < n + 1; i++) {
        terms.push(t);
        t *= r;
    }
    const sumFirstN = terms.slice(0, n).reduce((a, b) => a + b, 0);
    const sumFirstNMinus1 = terms.slice(0, n - 1).reduce((a, b) => a + b, 0);
    const sumFirstNPlus1 = terms.slice(0, n + 1).reduce((a, b) => a + b, 0);
    const lastTerm = terms[n - 1];

    const correct = sumFirstN;
    const question = `Find the sum of the first ${n} terms of the geometric sequence: ${a1}, ${a1 * r}, ${a1 * r ** 2}, …`;
    const distractors = selectUniqueDistractors(String(correct), [
        String(sumFirstNMinus1),
        String(sumFirstNPlus1),
        String(lastTerm),
        String(correct + a1),
        String(correct - a1),
    ]);
    const { options, answer } = assembleOptions(String(correct), distractors);
    return { question, options, answer };
}

/* ================================================================
   3. harmonic_sequence — reciprocals of an arithmetic sequence
   ================================================================ */
function genHarmonicSequence(difficulty) {
    const ranges = {
        easy: { b1: [2, 6], d: [1, 3], n: [4, 6] },
        medium: { b1: [2, 10], d: [1, 5], n: [4, 8] },
        hard: { b1: [2, 15], d: [2, 8], n: [5, 10] },
    }[difficulty];

    const b1 = randInt(ranges.b1[0], ranges.b1[1]);
    const d = randInt(ranges.d[0], ranges.d[1]);
    // n starts at 4: the question preview always shows exactly 3 terms
    // (1/b1, 1/(b1+d), 1/(b1+2d)), so n=3 would hand the answer to the
    // student directly inside the question stem.
    const n = randInt(ranges.n[0], ranges.n[1]);

    if (Math.random() < 0.5) {
        const denom = b1 + (n - 1) * d;
        const correct = new Fraction(1, denom).toDisplayString();
        const question = `Find the ${n}th term of the harmonic sequence: 1/${b1}, 1/${b1 + d}, 1/${b1 + 2 * d}, …`;

        const candidates = [
            new Fraction(1, b1 + n * d).toDisplayString(),
            String(denom), // forgot to take the reciprocal
            new Fraction(1, denom + 1).toDisplayString(),
            new Fraction(1, denom + 2).toDisplayString(),
        ];
        const altDenom = b1 - (n - 1) * d;
        if (altDenom > 0) {
            candidates.push(new Fraction(1, altDenom).toDisplayString());
        }

        const distractors = selectUniqueDistractors(correct, candidates);
        const { options, answer } = assembleOptions(correct, distractors);
        return { question, options, answer };
    }

    // Insert one harmonic mean between 1/b1 and 1/(b1+2d).
    const term2Denom = b1 + 2 * d;
    const meanDenom = b1 + d;
    const correct = new Fraction(1, meanDenom).toDisplayString();
    const question = `Insert one harmonic mean between 1/${b1} and 1/${term2Denom}.`;
    const candidates = [
        new Fraction(1, b1).toDisplayString(),
        new Fraction(1, term2Denom).toDisplayString(),
        new Fraction(1, meanDenom + 1).toDisplayString(),
        new Fraction(1, meanDenom + 2).toDisplayString(),
    ];
    const distractors = selectUniqueDistractors(correct, candidates);
    const { options, answer } = assembleOptions(correct, distractors);
    return { question, options, answer };
}

/* ================================================================
   4. fibonacci_sequence — F(k) = F(k−1) + F(k−2), F(1)=F(2)=1
   ================================================================ */
function fibonacci(k) {
    if (k === 1 || k === 2) {
        return 1;
    }
    let f1 = 1;
    let f2 = 1;
    for (let i = 3; i <= k; i++) {
        const next = f1 + f2;
        f1 = f2;
        f2 = next;
    }
    return f2;
}

function genFibonacciSequence(difficulty) {
    const [min, max] = { easy: [2, 12], medium: [10, 20], hard: [18, 28] }[difficulty];
    const k = randInt(min, max);
    const fk = fibonacci(k);
    const fk1 = fibonacci(k + 1);
    const correct = fk + fk1;

    const question = `The ${k}th Fibonacci number is ${fk} and the ${k + 1}th is ${fk1}. What is the ${k + 2}th Fibonacci number?`;
    const distractors = selectUniqueDistractors(String(correct), [
        String(Math.abs(fk1 - fk)),
        String(fk * fk1),
        String(2 * fk1),
        String(correct + 1),
        String(correct - 1),
    ]);
    const { options, answer } = assembleOptions(String(correct), distractors);
    return { question, options, answer };
}

/* ================================================================
   5. finite_infinite — recover the term count of a finite sequence
   ================================================================ */
function genFiniteInfinite(difficulty) {
    const ranges = {
        easy: { a1: [1, 20], d: [1, 8], n: [5, 15] },
        medium: { a1: [-10, 20], d: [1, 12], n: [10, 25] },
        hard: { a1: [-30, 30], d: [1, 20], n: [15, 40] },
    }[difficulty];

    const a1 = randInt(ranges.a1[0], ranges.a1[1]);
    const d = randInt(ranges.d[0], ranges.d[1]);
    const n = randInt(ranges.n[0], ranges.n[1]);
    const L = a1 + (n - 1) * d;

    const correct = n;
    const question = `The finite sequence ${a1}, ${a1 + d}, ${a1 + 2 * d}, …, ${L} has how many terms?`;
    const distractors = selectUniqueDistractors(String(correct), [
        String(Math.round((L - a1) / d)),
        String(Math.round((L - a1) / d) + 2),
        String(Math.round(L / d)),
        String(n + 1),
        String(n - 1),
    ]);
    const { options, answer } = assembleOptions(String(correct), distractors);
    return { question, options, answer };
}

/* ================================================================
   6. division_polynomials — P(x) = (x−k)Q(x)+r, find a quotient coeff
   ================================================================ */
function genDivisionPolynomials(difficulty) {
    const ranges = {
        easy: { k: [-5, 5], q2: [1, 3], coeff: [-5, 5] },
        medium: { k: [-8, 8], q2: [1, 4], coeff: [-9, 9] },
        hard: { k: [-10, 10], q2: [1, 5], coeff: [-12, 12] },
    }[difficulty];

    const k = randIntExcept(ranges.k[0], ranges.k[1], 0);
    const q2 = randInt(ranges.q2[0], ranges.q2[1]);
    const q1 = randInt(ranges.coeff[0], ranges.coeff[1]);
    const q0 = randInt(ranges.coeff[0], ranges.coeff[1]);
    const r = randInt(ranges.coeff[0], ranges.coeff[1]);

    // Build P(x) = (x-k)(q2x²+q1x+q0) + r by direct expansion.
    const c3 = q2;
    const c2 = q1 - k * q2;
    const c1 = q0 - k * q1;
    const c0 = -k * q0 + r;

    const { quotient, remainder } = syntheticDivide([c3, c2, c1, c0], k);
    const correct = quotient[2]; // constant term of the quotient

    const question = `Using synthetic division, find the constant term of the quotient when P(x) = ${c3}x³${formatSignedTerm(c2, 'x²')}${formatSignedTerm(c1, 'x')}${formatSignedTerm(c0, '')} is divided by (x ${k < 0 ? '+' : '−'} ${Math.abs(k)}).`;

    const altDivide = syntheticDivide([c3, c2, c1, c0], -k);
    const distractors = selectUniqueDistractors(String(correct), [
        String(quotient[1]),
        String(remainder),
        String(altDivide.quotient[2]),
        String(correct + 1),
        String(correct - 1),
    ]);
    const { options, answer } = assembleOptions(String(correct), distractors);
    return { question, options, answer };
}

/* ================================================================
   7. remainder_theorem — P(ρ) via Horner, and the Factor Theorem
   ================================================================ */
function genRemainderTheorem(difficulty) {
    const ranges = {
        easy: { c3: [1, 3], coeff: [-9, 9], rho: [-4, 4], L: [1, 3], BC: [-6, 6] },
        medium: { c3: [1, 4], coeff: [-15, 15], rho: [-6, 6], L: [1, 5], BC: [-10, 10] },
        hard: { c3: [1, 6], coeff: [-20, 20], rho: [-8, 8], L: [2, 6], BC: [-15, 15] },
    }[difficulty];

    if (Math.random() < 0.5) {
        const c3 = randInt(ranges.c3[0], ranges.c3[1]);
        const c2 = randInt(ranges.coeff[0], ranges.coeff[1]);
        const c1 = randInt(ranges.coeff[0], ranges.coeff[1]);
        const c0 = randInt(ranges.coeff[0], ranges.coeff[1]);
        const rho = randIntExcept(ranges.rho[0], ranges.rho[1], 0);

        const correct = c3 * rho ** 3 + c2 * rho ** 2 + c1 * rho + c0;
        const question = `Find the remainder when P(x) = ${c3}x³${formatSignedTerm(c2, 'x²')}${formatSignedTerm(c1, 'x')}${formatSignedTerm(c0, '')} is divided by (x ${rho < 0 ? '+' : '−'} ${Math.abs(rho)}).`;

        const pNegRho = c3 * (-rho) ** 3 + c2 * (-rho) ** 2 + c1 * (-rho) + c0;
        const hornerNoConst = c3 * rho ** 3 + c2 * rho ** 2 + c1 * rho;
        const sumCoeffs = c3 + c2 + c1 + c0;
        const distractors = selectUniqueDistractors(String(correct), [
            String(pNegRho),
            String(hornerNoConst),
            String(sumCoeffs),
            String(correct + 1),
            String(correct - 1),
        ]);
        const { options, answer } = assembleOptions(String(correct), distractors);
        return { question, options, answer };
    }

    // Factor Theorem: find the unknown coefficient given a root of ±1
    // (every power of ±1 is ±1, so isolating the unknown never needs
    // a non-trivial division — guarantees an exact answer).
    const rho = Math.random() < 0.5 ? 1 : -1;
    const L = randInt(ranges.L[0], ranges.L[1]);
    const B = randInt(ranges.BC[0], ranges.BC[1]);
    const C = randInt(ranges.BC[0], ranges.BC[1]);
    const correct = rho * (L + B) + C;

    const question = `Find the value of m so that (x ${rho === 1 ? '−' : '+'} 1) is a factor of ${L}x³ − mx²${formatSignedTerm(B, 'x')}${formatSignedTerm(C, '')}.`;
    const distractors = selectUniqueDistractors(String(correct), [
        String(-(rho * (L + B) + C)),
        String(rho * (L + B) - C),
        String((L + B) + C),
        String(correct + 1),
        String(correct - 1),
    ]);
    const { options, answer } = assembleOptions(String(correct), distractors);
    return { question, options, answer };
}

/* ================================================================
   8. polynomial_equations — Vieta's formulas from chosen integer roots
   ================================================================ */
function genPolynomialEquations(difficulty) {
    const [min, max] = { easy: [-4, 4], medium: [-7, 7], hard: [-10, 10] }[difficulty];
    const [r1, r2, r3] = sampleDistinct(min, max, 3);

    const e1 = r1 + r2 + r3; // sum of roots
    const e2 = r1 * r2 + r1 * r3 + r2 * r3;
    const e3 = r1 * r2 * r3; // product of roots

    const polyStr = `x³${formatSignedTerm(-e1, 'x²')}${formatSignedTerm(e2, 'x')}${formatSignedTerm(-e3, '')} = 0`;

    if (Math.random() < 0.5) {
        const correct = e1;
        const question = `Find the sum of the roots of ${polyStr}`;
        const distractors = selectUniqueDistractors(String(correct), [
            String(-e1),
            String(e2),
            String(e3),
            String(correct + 1),
            String(correct - 1),
        ]);
        const { options, answer } = assembleOptions(String(correct), distractors);
        return { question, options, answer };
    }

    const correct = e1 - r1;
    const question = `One of the roots of ${polyStr} is x = ${r1}. Find the sum of the other two roots.`;
    const distractors = selectUniqueDistractors(String(correct), [
        String(e1),
        String(-(e1 - r1)),
        String(r1),
        String(correct + 1),
        String(correct - 1),
    ]);
    const { options, answer } = assembleOptions(String(correct), distractors);
    return { question, options, answer };
}

/* ================================================================
   9. rational_equations — (x+a)/(x−h) = p, built backwards from x0
   ================================================================ */
function genRationalEquations(difficulty) {
    const ranges = {
        easy: { x0: [-6, 6], p: [-3, -2, -1, 2, 3, 4] },
        medium: { x0: [-10, 10], p: [-5, -4, -3, -2, 2, 3, 4, 5, 6] },
        hard: { x0: [-15, 15], p: [-8, -7, -6, -5, -4, -3, -2, 2, 3, 4, 5, 6, 7, 8] },
    }[difficulty];

    const x0 = randInt(ranges.x0[0], ranges.x0[1]);
    const p = pickFrom(ranges.p); // never 0 or 1
    const h = randIntExcept(ranges.x0[0], ranges.x0[1], x0);

    const a = x0 * (p - 1) - p * h;

    const question = `Solve for x: (x ${a < 0 ? '−' : '+'} ${Math.abs(a)}) / (x ${h < 0 ? '+' : '−'} ${Math.abs(h)}) = ${p}. Check for extraneous solutions.`;

    const correct = x0;
    const misDistributed = Math.round((h + a) / (p - 1));
    const distractors = selectUniqueDistractors(String(correct), [
        String(h), // the classic extraneous-value trap
        String(misDistributed),
        String(-x0),
        String(correct + 1),
        String(correct - 1),
    ]);
    const { options, answer } = assembleOptions(String(correct), distractors);
    return { question, options, answer };
}

/* ================================================================
   10. radical_equations — √ built backwards from x0, ∛ never extraneous
   ================================================================ */
function genRadicalSquareRoot(difficulty) {
    const cfg = {
        easy: { x0: [3, 10], k: [0, 3], A: [1, 2, 3] },
        medium: { x0: [2, 15], k: [-5, 5], A: [-4, -3, -2, -1, 1, 2, 3, 4] },
        hard: { x0: [-5, 20], k: [-10, 10], A: [-6, -5, -4, -3, -2, -1, 1, 2, 3, 4, 5, 6] },
    }[difficulty];

    let x0 = randInt(cfg.x0[0], cfg.x0[1]);
    let k = randInt(cfg.k[0], cfg.k[1]);
    let A = pickFrom(cfg.A);
    let B = (x0 - k) ** 2 - A * x0;
    let x1 = (2 * k + A) - x0;

    for (let attempt = 0; attempt < 30 && !(x0 > k && x1 < k); attempt++) {
        x0 = randInt(cfg.x0[0], cfg.x0[1]);
        k = randInt(cfg.k[0], cfg.k[1]);
        A = pickFrom(cfg.A);
        B = (x0 - k) ** 2 - A * x0;
        x1 = (2 * k + A) - x0;
    }
    if (!(x0 > k && x1 < k)) {
        // Known-good fallback: x0=3, k=0, A=2 -> B=3, x1=-1 (<0=k). Always valid.
        A = 2;
        k = 0;
        x0 = 3;
        B = (x0 - k) ** 2 - A * x0;
        x1 = (2 * k + A) - x0;
    }

    const correct = x0;
    const question = `Solve: √(${A}x ${B >= 0 ? '+' : '−'} ${Math.abs(B)}) = x ${k >= 0 ? '−' : '+'} ${Math.abs(k)}. Check for extraneous solutions.`;
    const distractors = selectUniqueDistractors(String(correct), [
        String(x1), // the genuine extraneous root
        String(-x0),
        String(k),
        String(correct + 1),
        String(correct - 1),
    ]);
    const { options, answer } = assembleOptions(String(correct), distractors);
    return { question, options, answer };
}

function genRadicalCubeRoot(difficulty) {
    const cfg = {
        easy: { x0: [-6, 6], A: [1, 3], c: [-4, 4] },
        medium: { x0: [-10, 10], A: [1, 4], c: [-4, 4] },
        hard: { x0: [-15, 15], A: [1, 5], c: [-4, 4] },
    }[difficulty];

    const x0 = randInt(cfg.x0[0], cfg.x0[1]);
    const A = randInt(cfg.A[0], cfg.A[1]);
    const c = randInt(cfg.c[0], cfg.c[1]);
    const B = c ** 3 - A * x0;

    const correct = x0;
    const question = `Solve: ∛(${A}x ${B >= 0 ? '+' : '−'} ${Math.abs(B)}) = ${c}.`;
    const distractors = selectUniqueDistractors(String(correct), [
        String(-x0),
        String(c),
        String(x0 + 2),
        String(x0 - 2),
        String(correct + 1),
    ]);
    const { options, answer } = assembleOptions(String(correct), distractors);
    return { question, options, answer };
}

function genRadicalEquations(difficulty) {
    return Math.random() < 0.5 ? genRadicalSquareRoot(difficulty) : genRadicalCubeRoot(difficulty);
}

/* ================================================================
   11. exponential_functions — b^(mx+c) = N, and a doubling word problem
   ================================================================ */
function genExponentialFunctions(difficulty) {
    const cfg = {
        easy: { bVals: [2, 3], mVals: [1, 2], c1: [0, 3], x0: [1, 5], n: [1, 5] },
        medium: { bVals: [2, 3, 5], mVals: [-4, -3, -2, -1, 1, 2, 3, 4], c1: [-5, 5], x0: [-5, 10], n: [1, 6] },
        hard: { bVals: [2, 3, 5, 10], mVals: [-5, -4, -3, -2, -1, 1, 2, 3, 4, 5], c1: [-8, 8], x0: [-10, 15], n: [1, 8] },
    }[difficulty];

    if (Math.random() < 0.5) {
        const b = pickFrom(cfg.bVals);
        let m = pickFrom(cfg.mVals);
        let c1 = randInt(cfg.c1[0], cfg.c1[1]);
        let x0 = randInt(cfg.x0[0], cfg.x0[1]);
        let k = m * x0 + c1;

        for (let attempt = 0; attempt < 30 && (k < 0 || b ** k > 1e6); attempt++) {
            m = pickFrom(cfg.mVals);
            c1 = randInt(cfg.c1[0], cfg.c1[1]);
            x0 = randInt(cfg.x0[0], cfg.x0[1]);
            k = m * x0 + c1;
        }
        if (k < 0 || b ** k > 1e6) {
            m = 2;
            c1 = 0;
            x0 = 2;
            k = 4;
        }
        const N = b ** k;

        const correct = x0;
        const question = `Solve for x: ${b}^(${m}x${formatSignedTerm(c1, '')}) = ${N}.`;
        const distractors = selectUniqueDistractors(String(correct), [
            String(k),
            String(Math.round((k - c1) * m)),
            String(-x0),
            String(correct + 1),
            String(correct - 1),
        ]);
        const { options, answer } = assembleOptions(String(correct), distractors);
        return { question, options, answer };
    }

    const P0 = randInt(1, 20) * 100;
    const p = pickFrom([2, 3, 4, 5]);
    const n = randInt(cfg.n[0], cfg.n[1]);
    const t = p * n;

    const correct = P0 * 2 ** n;
    const question = `The population of a town doubles every ${p} years. Starting at ${P0}, what is the population after ${t} years?`;
    const distractors = selectUniqueDistractors(String(correct), [
        String(P0 * 2 ** (n + 1)),
        String(P0 * n * 2),
        String(P0 + n * P0),
        String(correct + P0),
        String(correct - P0),
    ]);
    const { options, answer } = assembleOptions(String(correct), distractors);
    return { question, options, answer };
}

/* ================================================================
   12. logarithmic_functions — log_b(N)=k, and solve log_b(x)=k for x
   ================================================================ */
function genLogarithmicFunctions(difficulty) {
    const cfg = {
        easy: { bVals: [2, 3], kT1: [1, 4], kT2: [1, 4] },
        medium: { bVals: [2, 3, 5], kT1: [1, 5], kT2: [-2, 5] },
        hard: { bVals: [2, 3, 5, 10], kT1: [1, 6], kT2: [-3, 6] },
    }[difficulty];
    const b = pickFrom(cfg.bVals);

    if (Math.random() < 0.5) {
        const k = randInt(cfg.kT1[0], cfg.kT1[1]);
        const N = b ** k;
        const correct = k;
        const question = `Evaluate: log${toSubscript(b)}(${N}).`;
        const distractors = selectUniqueDistractors(String(correct), [
            String(N),
            String(b * k),
            String(k + 1),
            String(k - 1),
            String(k + 2),
        ]);
        const { options, answer } = assembleOptions(String(correct), distractors);
        return { question, options, answer };
    }

    const k = randInt(cfg.kT2[0], cfg.kT2[1]);
    const powOrFraction = (exp) => (exp >= 0 ? String(b ** exp) : new Fraction(1, b ** -exp).toDisplayString());
    const correct = powOrFraction(k);

    const question = `Solve: log${toSubscript(b)}(x) = ${k}.`;
    const distractors = selectUniqueDistractors(correct, [
        String(k * b),
        powOrFraction(k + 1),
        powOrFraction(k - 1),
        String(k),
    ]);
    const { options, answer } = assembleOptions(correct, distractors);
    return { question, options, answer };
}

/* ------------------------------------------------------------------
   Dispatcher
------------------------------------------------------------------ */
const DETERMINISTIC_GENERATORS = {
    arithmetic_sequence: genArithmeticSequence,
    geometric_sequence: genGeometricSequence,
    harmonic_sequence: genHarmonicSequence,
    fibonacci_sequence: genFibonacciSequence,
    finite_infinite: genFiniteInfinite,
    division_polynomials: genDivisionPolynomials,
    remainder_theorem: genRemainderTheorem,
    polynomial_equations: genPolynomialEquations,
    rational_equations: genRationalEquations,
    radical_equations: genRadicalEquations,
    exponential_functions: genExponentialFunctions,
    logarithmic_functions: genLogarithmicFunctions,
};

const VALID_DIFFICULTIES = new Set(['easy', 'medium', 'hard']);
function normalizeDifficulty(difficulty) {
    return VALID_DIFFICULTIES.has(difficulty) ? difficulty : 'medium';
}

export function isDeterministicTopic(activityValue) {
    return Object.prototype.hasOwnProperty.call(DETERMINISTIC_GENERATORS, activityValue);
}

export function generateDeterministicQuestion(activityValue, difficulty) {
    const gen = DETERMINISTIC_GENERATORS[activityValue];
    if (!gen) {
        return null;
    }
    return gen(normalizeDifficulty(difficulty));
}

// A candidate's distractor pool can (rarely) collide down to fewer than
// 3 unique wrong values for some parameter draws — this guards against
// ever showing a question with fewer than 4 real options.
function isWellFormed(candidate) {
    const keys = Object.keys(candidate.options || {});
    return keys.length === 4
        && !!candidate.answer
        && candidate.options[candidate.answer] !== undefined
        && new Set(keys.map((k) => candidate.options[k])).size === 4;
}

/* ------------------------------------------------------------------
   Number-preservation check — used by teacher_dashboard.js when an AI
   rephrases a deterministic question's wording for variety. The AI's
   job there is pure paraphrasing (no math, no answer-finding), but we
   still verify it didn't drop/alter a number before trusting its
   wording — if it did, the caller falls back to the original,
   already-verified template question instead.
------------------------------------------------------------------ */
export function extractDigitRuns(text) {
    return String(text ?? '').match(/\d+/g) || [];
}

export function rewritePreservesNumbers(originalQuestion, rewrittenText) {
    if (!rewrittenText || typeof rewrittenText !== 'string' || rewrittenText.trim() === '') {
        return false;
    }
    const originalNumbers = extractDigitRuns(originalQuestion);
    const rewrittenNumbers = new Set(extractDigitRuns(rewrittenText));
    return originalNumbers.every((n) => rewrittenNumbers.has(n));
}

export function generateDeterministicSet(activityValue, difficulty, count) {
    const gen = DETERMINISTIC_GENERATORS[activityValue];
    if (!gen) {
        return null; // signals the caller to fall back to the AI path
    }

    const safeDifficulty = normalizeDifficulty(difficulty);
    const seen = new Set();
    const out = [];
    const MAX_ATTEMPTS_PER_ITEM = 25;

    for (let i = 0; i < count; i++) {
        let item = null;
        for (let attempt = 0; attempt < MAX_ATTEMPTS_PER_ITEM; attempt++) {
            const candidate = gen(safeDifficulty);
            if (!isWellFormed(candidate)) {
                continue; // malformed draw (rare distractor collision) — reroll
            }
            const key = candidate.question.trim().toLowerCase();
            item = candidate;
            if (!seen.has(key)) {
                seen.add(key);
                break;
            }
        }
        if (item) {
            out.push(item);
        }
    }
    return out;
}
