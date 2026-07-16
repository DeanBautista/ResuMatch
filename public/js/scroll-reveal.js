/**
 * js/scroll-reveal.js
 *
 * Triggers the reveal animations defined in css/results-reveal.css.
 * Any element (or group) marked with [data-reveal] / [data-reveal-group]
 * starts hidden/offset via CSS, and gets .is-visible added the first time
 * it scrolls into the viewport — then we stop observing it, so it plays
 * once per page load, same spirit as home.php's one-shot entrance.
 *
 * Include this on results.php only, after results-reveal.css and after
 * the DOM it targets (or defer it — see script tag notes at bottom).
 */

(function () {
  "use strict";

  // No IntersectionObserver support (very old browser) — just show
  // everything immediately rather than leaving content invisible.
  if (!("IntersectionObserver" in window)) {
    document
      .querySelectorAll("[data-reveal], [data-reveal-group]")
      .forEach(function (el) {
        el.classList.add("is-visible");
      });
    restoreRingFillsImmediately();
    restoreScoreNumbersImmediately();
    return;
  }

  let reduceMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
  ).matches;

  // Groups reveal as a whole (parent gets .is-visible, which is what lets
  // the nth-child stagger delays in results-reveal.css kick in for its
  // children). Individual [data-reveal] elements not inside a group
  // reveal independently.
  let groups = document.querySelectorAll("[data-reveal-group]");
  let loneItems = document.querySelectorAll(
    "[data-reveal]:not([data-reveal-group] > [data-reveal])"
  );

    let observerOptions = {
    root: null,
    threshold: 0.01,
    };

  function revealBarFill(el) {
    // Bars keep their true width in the inline style attribute (set by
    // PHP: style="width: 84%"). We stash it, zero it out until visible,
    // then restore it so the transition in CSS animates the real value.
    if (el.getAttribute("data-reveal") !== "bar-fill") return;
    let target = el.style.width;
    if (target && !el.dataset.revealTarget) {
      el.dataset.revealTarget = target;
      el.style.width = "0%";
    }
  }

  function revealRingFill(el) {
    // Same idea as revealBarFill, but for the score-card ring: the real
    // stroke-dashoffset lives in the inline style (set by PHP as the
    // "filled" position). We stash it, push the ring to fully empty
    // (offset === its own dasharray, i.e. the full circumference), then
    // restore the true offset so the CSS transition animates the fill.
    if (el.getAttribute("data-reveal") !== "ring-fill") return;
    let target = el.style.strokeDashoffset;
    if (target && !el.dataset.revealTarget) {
      el.dataset.revealTarget = target;
      el.style.strokeDashoffset = el.getAttribute("stroke-dasharray");
    }
  }

  function restoreRingFillsImmediately() {
    document.querySelectorAll('[data-reveal="ring-fill"]').forEach(function (ring) {
      if (ring.dataset.revealTarget) {
        ring.style.strokeDashoffset = ring.dataset.revealTarget;
      }
    });
  }

  function restoreScoreNumbersImmediately() {
    document.querySelectorAll('[data-reveal="score-number"]').forEach(function (el) {
      let target = el.getAttribute("data-reveal-target");
      if (target !== null) {
        el.textContent = target;
      }
    });
  }

  function animateRingFill(ring) {
    // Driven by JS (not just a CSS transition) so it can share the exact
    // same two-segment timing curve as animateScoreNumber, keeping the
    // ring and the number perfectly in sync regardless of target size.
    let dasharray = parseFloat(ring.getAttribute("stroke-dasharray"));
    let targetOffsetStr = ring.dataset.revealTarget;
    if (!targetOffsetStr || isNaN(dasharray)) return;

    let targetOffset = parseFloat(targetOffsetStr);
    let startOffset = dasharray; // fully empty
    let midOffset = startOffset - (startOffset - targetOffset) / 2; // 50% filled

    let duration = 600;
    let rampFraction = 0.25;
    let start = null;

    function step(timestamp) {
      if (!start) start = timestamp;
      let elapsed = timestamp - start;
      let progress = Math.min(elapsed / duration, 1);
      let value;

      if (progress < rampFraction) {
        // Segment 1: fast linear ramp start -> midOffset
        let segProgress = progress / rampFraction;
        value = startOffset + segProgress * (midOffset - startOffset);
      } else {
        // Segment 2: ease-out midOffset -> targetOffset
        let segProgress = (progress - rampFraction) / (1 - rampFraction);
        let eased = 1 - Math.pow(1 - segProgress, 4); // ease-out quartic
        value = midOffset + eased * (targetOffset - midOffset);
      }

      ring.style.strokeDashoffset = value;

      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        ring.style.strokeDashoffset = targetOffset;
      }
    }
    requestAnimationFrame(step);
  }

  function animateScoreNumber(el) {
    // Two-segment animation so the "quick jump then settle" feel is
    // consistent no matter how big or small the target is. Without this,
    // a steep ease-out curve applied uniformly in time means low targets
    // (e.g. 40%) barely animate at all — the curve reaches ~37% almost
    // instantly, so it doesn't read as motion.
    //
    // Segment 1 (first 25% of duration): fast ramp from 0 -> 50% of target.
    // Segment 2 (remaining 75% of duration): ease-out from 50% -> target.
    //
    // This guarantees every score, regardless of size, visibly starts
    // around half its final value and eases into place from there.
    let target = parseInt(el.getAttribute("data-reveal-target"), 10);
    if (isNaN(target)) return;

    let duration = 600;
    let rampFraction = 0.25; // portion of duration spent on segment 1
    let midPoint = target / 2;
    let start = null;

    function step(timestamp) {
      if (!start) start = timestamp;
      let elapsed = timestamp - start;
      let progress = Math.min(elapsed / duration, 1);
      let value;

      if (progress < rampFraction) {
        // Segment 1: fast linear ramp 0 -> midPoint
        let segProgress = progress / rampFraction;
        value = segProgress * midPoint;
      } else {
        // Segment 2: ease-out midPoint -> target
        let segProgress = (progress - rampFraction) / (1 - rampFraction);
        let eased = 1 - Math.pow(1 - segProgress, 4); // ease-out quartic
        value = midPoint + eased * (target - midPoint);
      }

      el.textContent = Math.round(value);

      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        el.textContent = target;
      }
    }
    requestAnimationFrame(step);
  }

  function onIntersect(entries, observer) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      let el = entry.target;
      el.classList.add("is-visible");

      // Restore bar-fill widths now that .is-visible triggers the CSS
      // transition. Ring fills are animated via JS (animateRingFill) so
      // they can share the same two-segment curve as the score number.
      if (el.dataset.revealTarget) {
        if (el.getAttribute("data-reveal") === "bar-fill") {
          requestAnimationFrame(function () {
            el.style.width = el.dataset.revealTarget;
          });
        } else if (el.getAttribute("data-reveal") === "ring-fill") {
          animateRingFill(el);
        }
      } else {
        el.querySelectorAll('[data-reveal="bar-fill"]').forEach(function (bar) {
          if (bar.dataset.revealTarget) {
            requestAnimationFrame(function () {
              bar.style.width = bar.dataset.revealTarget;
            });
          }
        });
        el.querySelectorAll('[data-reveal="ring-fill"]').forEach(function (ring) {
          if (ring.dataset.revealTarget) {
            animateRingFill(ring);
          }
        });
      }

      // Count up any score numbers nested in (or equal to) the revealed element.
      if (el.matches('[data-reveal="score-number"]')) {
        animateScoreNumber(el);
      } else {
        el.querySelectorAll('[data-reveal="score-number"]').forEach(animateScoreNumber);
      }

      observer.unobserve(el);
    });
  }

  // Pre-zero bar fills and ring fills up front so there's no flash of
  // full-width bars / full rings before JS has a chance to observe them.
  document.querySelectorAll('[data-reveal="bar-fill"]').forEach(revealBarFill);
  document.querySelectorAll('[data-reveal="ring-fill"]').forEach(revealRingFill);

  if (reduceMotion) {
    // Skip the observer dance entirely; just show final state and
    // restore bar widths / ring offsets / score numbers immediately.
    document
      .querySelectorAll("[data-reveal], [data-reveal-group]")
      .forEach(function (el) {
        el.classList.add("is-visible");
        if (el.dataset.revealTarget) {
          if (el.getAttribute("data-reveal") === "bar-fill") {
            el.style.width = el.dataset.revealTarget;
          } else if (el.getAttribute("data-reveal") === "ring-fill") {
            el.style.strokeDashoffset = el.dataset.revealTarget;
          }
        }
      });
    restoreRingFillsImmediately();
    restoreScoreNumbersImmediately();
    return;
  }

  let observer = new IntersectionObserver(onIntersect, observerOptions);

  groups.forEach(function (el) {
    observer.observe(el);
  });
  loneItems.forEach(function (el) {
    observer.observe(el);
  });
})();