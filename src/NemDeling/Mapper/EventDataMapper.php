<?php

declare(strict_types=1);

namespace App\NemDeling\Mapper;

use App\Entity\Template;
use App\Entity\Tenant;
use App\Entity\Tenant\Screen;
use App\NemDeling\Exception\NemDelingConfigurationException;
use App\NemDeling\Model\NemDelingSlide;
use App\NemDeling\NemDelingConfig;
use App\Repository\ScreenRepository;
use App\Repository\TemplateRepository;
use App\Repository\TenantRepository;

final class EventDataMapper
{
    private const array COLOR_MAP = [
        'sort' => '#000000',
        'kk_blaa' => '#000c2e',
        'blaa' => '#002CFC',
        'marine_blaa' => '#260EB5',
        'stoev_blaa' => '#025FCC',
        'moerk_stoev_blaa' => '#00519C',
        'graa_blaa' => '#1271A6',
        'roed' => '#C10023',
        'rust_roed' => '#BD3615',
        'moerk_rosa' => '#CD274F',
        'bordeaux' => '#900009',
        'lilla' => '#8332EB',
        'groen' => '#047C6E',
        'blaa_groen' => '#00777E',
        'brun' => '#5E4347',
        'bronze' => '#926B1F',
        'moerke_graa' => '#665E62',
        'prismen' => '#428515',
        'gmc' => '#0c807e',
        'blaagaarden' => '#116B91',
        'huset' => '#c7e2df',
        'kiby' => '#153d44',
    ];

    private const array COLOR_PALETTE_MAP = [
        'farvepar1' => 'farvepar1',
        'farvepar2' => 'farvepar2',
        'farvepar3' => 'farvepar3',
    ];

    public function __construct(
        private readonly NemDelingConfig $config,
        private readonly TenantRepository $tenantRepository,
        private readonly TemplateRepository $templateRepository,
        private readonly ScreenRepository $screenRepository,
    ) {}

    /**
     * @param array<string, mixed> $body
     *
     * @return array{result: array<string, NemDelingSlide[]>, notFound: string[]}
     */
    public function map(array $body, string $templateTitle, string $slideType): array
    {
        $tenant = $this->resolveTenant();
        $template = $this->templateRepository->findOneBy(['title' => $templateTitle]);
        if (!$template instanceof Template) {
            throw new \RuntimeException(sprintf('No template found for title "%s".', $templateTitle));
        }

        $templateId = (string) $template->getId();
        $result = [];
        $notFound = [];

        /** @var Screen[] $screens */
        $screens = $this->screenRepository->findBy(['tenant' => $tenant]);
        foreach ($screens as $screen) {
            $title = $screen->getTitle();
            if ('' !== $title) {
                $result[$title] = [];
            }
        }

        $items = $body['result']['item'] ?? [];
        if (!is_array($items)) {
            $items = [$items];
        }

        usort($items, function (array $a, array $b): int {
            $startA = $this->firstValue($a['startdate'] ?? null);
            $startB = $this->firstValue($b['startdate'] ?? null);

            return $this->dateSortKey($startA) <=> $this->dateSortKey($startB);
        });

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $screenNames = $this->screenNames($item['screen'] ?? null);
            if ([] === $screenNames) {
                continue;
            }

            $timeValue = $this->firstValue($item['time'] ?? null);
            [$startTime, $endTime] = $this->splitTimeRange($timeValue);

            $startDate = $this->formatEventDate(
                $this->firstValue($item['startdate'] ?? null),
                "EEEE 'd'. d. MMMM"
            );
            $endDate = $this->formatEventDate(
                $this->firstValue($item['enddate'] ?? null),
                "EEEE 'd'. d. MMMM"
            );

            foreach ($screenNames as $screenName) {
                if (!array_key_exists($screenName, $result)) {
                    $notFound[] = $screenName;
                    continue;
                }

                $backgroundColor = '';
                $colorKey = $this->firstValue($item['color'] ?? null);
                if ('' !== $colorKey && array_key_exists($colorKey, self::COLOR_MAP)) {
                    $backgroundColor = self::COLOR_MAP[$colorKey];
                }

                $title = $this->firstValue($item['title'] ?? null);
                $alternativeTitle = $this->firstValue($item['alternativ_titel'] ?? null);
                if ('' !== $alternativeTitle) {
                    $title = $alternativeTitle;
                }

                $colorPalette = '';
                $paletteKey = $this->firstValue($item['farvepar'] ?? null);
                if ('' !== $paletteKey && array_key_exists($paletteKey, self::COLOR_PALETTE_MAP)) {
                    $colorPalette = self::COLOR_PALETTE_MAP[$paletteKey];
                }

                $result[$screenName][] = new NemDelingSlide(
                    templateId: $templateId,
                    content: [
                        'externalId' => $this->firstValue($item['nid'] ?? $item['Nid'] ?? null),
                        'title' => $title,
                        'subTitle' => $this->firstValue($item['field_teaser'] ?? null),
                        'host' => $this->firstValue($item['host'] ?? null),
                        'startDate' => '' !== $startDate ? sprintf('%s kl. %s', $startDate, $startTime) : '',
                        'endDate' => $startDate !== $endDate ? sprintf('%s kl. %s', $endDate, $endTime) : '',
                        'image' => $this->imageUrl($item['billede'] ?? null),
                        'bgColor' => $backgroundColor,
                        'colorPalette' => $colorPalette,
                    ],
                    type: $slideType,
                );
            }
        }

        return [
            'result' => $result,
            'notFound' => array_values(array_unique($notFound)),
        ];
    }

    /**
     * @param mixed $value
     */
    private function firstValue(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        if (!is_array($value)) {
            return '';
        }

        if (array_key_exists('item', $value)) {
            return $this->firstValue($value['item']);
        }

        if (array_is_list($value)) {
            return $this->firstValue($value[0] ?? null);
        }

        return '';
    }

    /**
     * @return string[]
     */
    private function screenNames(mixed $screen): array
    {
        if (!is_array($screen)) {
            return [];
        }

        $items = $screen['item'] ?? $screen;
        if (!is_array($items)) {
            return [] === $items ? [] : [(string) $items];
        }

        if (!array_is_list($items)) {
            $items = [$items];
        }

        return array_values(array_filter(array_map(
            fn (mixed $item): string => is_scalar($item) ? (string) $item : $this->firstValue($item),
            $items
        )));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitTimeRange(string $timeValue): array
    {
        if ('' === $timeValue || !str_contains($timeValue, ' til ')) {
            return ['', ''];
        }

        $parts = explode(' til ', $timeValue, 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    private function dateSortKey(string $date): int
    {
        if ('' === $date) {
            return 0;
        }

        $parts = explode('.', $date);
        if (3 !== count($parts)) {
            return 0;
        }

        return (int) sprintf('%s%s%s', $parts[2], $parts[1], $parts[0]);
    }

    /**
     * @param mixed $billede
     */
    private function imageUrl(mixed $billede): ?string
    {
        if (!is_array($billede)) {
            return null;
        }

        $items = $billede['item'] ?? $billede;
        if (!is_array($items)) {
            return null;
        }

        if (!array_is_list($items)) {
            $items = [$items];
        }

        $firstItem = $items[0] ?? null;
        if (!is_array($firstItem)) {
            return null;
        }

        $img = $firstItem['img'] ?? null;
        if (!is_array($img)) {
            return null;
        }

        $imgNode = $img[0] ?? $img;
        if (!is_array($imgNode)) {
            return null;
        }

        $attributes = $imgNode['$'] ?? null;
        if (!is_array($attributes)) {
            return null;
        }

        $src = $attributes['src'] ?? null;

        return is_string($src) && '' !== $src ? $src : null;
    }

    private function formatEventDate(string $dateString, string $pattern): string
    {
        if ('' === $dateString) {
            return '';
        }

        $parts = explode('.', $dateString);
        if (3 !== count($parts)) {
            return '';
        }

        [$day, $month, $year] = $parts;
        $date = new \DateTimeImmutable(
            sprintf('%s-%s-%s 00:00:00', $year, $month, $day),
            new \DateTimeZone('UTC')
        );

        $formatter = new \IntlDateFormatter(
            'da_DK',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            'UTC',
            null,
            $pattern
        );

        $formatted = $formatter->format($date);
        if (!is_string($formatted) || '' === $formatted) {
            return '';
        }

        return mb_strtoupper(mb_substr($formatted, 0, 1)).mb_substr($formatted, 1);
    }

    private function resolveTenant(): Tenant
    {
        $tenantKey = $this->config->getTenantKey();
        if ('' === $tenantKey) {
            throw new NemDelingConfigurationException('NEMDELING_TENANT_KEY is not configured.');
        }

        $tenant = $this->tenantRepository->findOneBy(['tenantKey' => $tenantKey]);
        if (!$tenant instanceof Tenant) {
            throw new NemDelingConfigurationException(sprintf('Tenant "%s" was not found.', $tenantKey));
        }

        return $tenant;
    }
}
