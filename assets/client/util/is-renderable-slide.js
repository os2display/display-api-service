/**
 * Whether a slide can actually be put on screen.
 *
 * A slide the pull could not load a template for is marked invalid and dropped
 * by Region. ScheduleService has to apply the same test when it decides whether
 * a region has content: counting slides the region will drop is what let a
 * screen whose templates all failed report itself as non-empty, so the fallback
 * image stayed hidden and the screen went black instead.
 *
 * An absent slide is not renderable either. `slide?.invalid !== true` answered
 * true for null and undefined, so a region holding nothing but absent slides
 * reported itself as having content - the same black screen this test exists to
 * prevent, reached from the other side.
 *
 * @param {object} slide The slide to test.
 * @returns {boolean} True if the slide should be rendered.
 */
export default function isRenderableSlide(slide) {
  return slide != null && slide.invalid !== true;
}
