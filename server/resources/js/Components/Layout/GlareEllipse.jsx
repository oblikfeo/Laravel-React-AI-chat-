/**
 * Затемнение под текстом — готовый слой от дизайнера (figma/fon).
 *
 * Кладётся внутрь блока с текстом и центрируется по нему, поэтому
 * совпадает с заголовком на любом экране. Привязанное к экрану, оно
 * уезжало от слов.
 */
export default function GlareEllipse() {
    return (
        <div
            aria-hidden
            className="pointer-events-none absolute left-1/2 top-1/2 -z-10 h-[340px] w-[1000px] max-w-[160vw] -translate-x-1/2 -translate-y-1/2 bg-contain bg-center bg-no-repeat sm:h-[420px] sm:w-[1200px]"
            style={{ backgroundImage: "url('/images/bg-ellipse.png')" }}
        />
    );
}
