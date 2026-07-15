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

  function onIntersect(entries, observer) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      let el = entry.target;
      el.classList.add("is-visible");

      // Restore bar-fill widths now that .is-visible triggers the
      // width transition defined in CSS.
      if (el.dataset.revealTarget) {
        // Next frame so the 0% -> target% change is picked up as a
        // transition rather than an instant jump.
        requestAnimationFrame(function () {
          el.style.width = el.dataset.revealTarget;
        });
      } else {
        el.querySelectorAll('[data-reveal="bar-fill"]').forEach(function (
          bar
        ) {
          if (bar.dataset.revealTarget) {
            requestAnimationFrame(function () {
              bar.style.width = bar.dataset.revealTarget;
            });
          }
        });
      }

      observer.unobserve(el);
    });
  }

  // Pre-zero bar fills up front so there's no flash of full-width bars
  // before JS has a chance to observe them.
  document.querySelectorAll('[data-reveal="bar-fill"]').forEach(revealBarFill);

  if (reduceMotion) {
    // Skip the observer dance entirely; just show final state and
    // restore bar widths immediately.
    document
      .querySelectorAll("[data-reveal], [data-reveal-group]")
      .forEach(function (el) {
        el.classList.add("is-visible");
        if (el.dataset.revealTarget) {
          el.style.width = el.dataset.revealTarget;
        }
      });
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