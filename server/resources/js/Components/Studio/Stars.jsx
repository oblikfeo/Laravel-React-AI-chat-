/**
 * Мерцающие звёзды поверх обложки работы, которая создаётся.
 *
 * Положение задано заранее, а не случайно при отрисовке: иначе звёзды
 * прыгали бы с места на место при каждом обновлении страницы.
 */
const STARS = [
    { top: '18%', left: '22%', size: 3, delay: 0 },
    { top: '32%', left: '68%', size: 2, delay: 0.4 },
    { top: '55%', left: '14%', size: 2, delay: 0.9 },
    { top: '70%', left: '52%', size: 3, delay: 0.2 },
    { top: '26%', left: '44%', size: 2, delay: 1.3 },
    { top: '78%', left: '78%', size: 2, delay: 0.7 },
    { top: '46%', left: '86%', size: 3, delay: 1.1 },
    { top: '62%', left: '34%', size: 2, delay: 1.6 },
];

export default function Stars() {
    return (
        <span aria-hidden="true" className="pointer-events-none absolute inset-0">
            {STARS.map((star, index) => (
                <span
                    key={index}
                    className="absolute rounded-full bg-sky-200"
                    style={{
                        top: star.top,
                        left: star.left,
                        width: `${star.size}px`,
                        height: `${star.size}px`,
                        boxShadow: '0 0 6px 1px rgb(125 211 252 / 0.8)',
                        animation: `work-twinkle 2.4s ease-in-out ${star.delay}s infinite`,
                    }}
                />
            ))}
        </span>
    );
}
