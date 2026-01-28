<?php
/**
 * Block template: CTC Comparison Table (CTC = Comparison Table Component)
 *
 * Displays a comparison table with company information including:
 * - companies (repeater)
 *   - company_id, company_name, logo_url, hq_market
 *   - average_rating, total_reviews
 *   - advertised_price, team_size, start_year, license
 *   - request_intro_enabled, review_link, website_url
 * 
 * Helper functions are included in this template file for simplicity.
 * In a production environment, these could be moved to a separate includes file.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Helper functions as noted above, would normally be in a separate file.
 * I'm putting them here for the sake of this POC.
 * Also using guard functions as its good practice to avoid redeclaring functions if the template is included multiple times.
 */


if (!function_exists('ctc_normalize_url')) {
	/**
	 * Normalizes a URL that might be missing scheme.
	 * Returns empty string if input is empty.
	 * Adds https:// prefix if no protocol is present.
	 */
	function ctc_normalize_url($url): string
	{
		$url = trim((string) $url);
		if ($url === '') return '';

		// if it already starts with http(s), keep it.
		if (preg_match('#^https?://#i', $url)) { // I ALWAYS look these up in a regex reference site like regex101.com
			return $url;
		}

		// Not allowing protocol-relative URLs here. Prefixing with https.
		return 'https://' . ltrim($url, '/');
	}
}

if (!function_exists('ctc_parse_int')) {
	/**
	 * Parses an integer from a string, removing non-numeric characters.
	 * Returns 0 if the string is empty or contains no digits.
	 */
	function ctc_parse_int($value): int
	{
		$s = (string) $value;
		$s = preg_replace('/[^\d]/', '', $s);
		return $s === '' ? 0 : (int) $s;
	}
}

if (!function_exists('ctc_format_reviews')) {
	/**
	 * Formats review counts with "k" suffix for values >= 1000.
	 * Example: 1500 becomes "1.5k", 500 remains "500".
	 * Returns empty string if value is 0 or not provided.
	 */
	function ctc_format_reviews($value): string
	{
		$n = ctc_parse_int($value);
		if ($n <= 0) return '';

		if ($n >= 1000) {
			$k = $n / 1000; 
			$k_str = number_format($k, 1); 
			return $k_str . 'k';
		}

		return (string) $n;
	}
}

if (!function_exists('ctc_compute_years')) {
	/**
	 * Computes the number of years since the start year.
	 * Returns null if the start year is not provided.
	 */
	function ctc_compute_years($start_year): ?int
	{
		$year = (int) $start_year;
		if ($year <= 0) return null;

		$current_year = (int) wp_date('Y');
		$years = $current_year - $year;

		return $years >= 0 ? $years : null;
	}
}

if (!function_exists('ctc_render_stars')) {
	/**
	 * Render 0-5 star row (simple full/empty).
	 */
	function ctc_render_stars(?float $rating): string
	{
		$rating = is_numeric($rating) ? (float) $rating : null;
		$filled = ($rating === null) ? 0 : max(0, min(5, (int) floor($rating)));

		// Using inline SVG for this POC.
		// Will not worry about half stars for this POC.
		$star_svg = '<svg class="ctc-cc__star" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.2 1 5.9L10 15.9 4.8 18.8l1-5.9L1.5 7.7l5.9-.9L10 1.5z"></path></svg>';

		$out = '<span class="ctc-cc__stars" aria-hidden="true">';
		for ($i = 0; $i < 5; $i++) {
			$out .= '<span class="ctc-cc__starWrap ' . ($i < $filled ? 'is-filled' : 'is-empty') . '">' . $star_svg . '</span>';
		}
		$out .= '</span>';

		return $out;
	}
}

if (!function_exists('ctc_pick_actions')) {
	/**
	 * Computes the primary and secondary actions based on the row data.
	 * Based on Link display priority rules.
	 * 
	 * For reference: 
	 * 1) request_intro_enabled
	 * 2) review_link
	 * 3) website_url
	 *
	 * If only one or two link fields are displayed, choose them based on this priority and the available data.
	 * 
	 * Returns:
	 * - primary: ['label','url','type']
	 * - secondary: optional
	 */
	function ctc_pick_actions(array $row): array
	{
		$request_intro = !empty($row['request_intro_enabled']);

		$review = ctc_normalize_url($row['review_link'] ?? '');
		$site   = ctc_normalize_url($row['website_url'] ?? '');

		$primary = null;
		$secondary = null;

		if ($request_intro) {
			$primary = [
				'label' => 'Get a Quote',
				'url'   => '#request-intro', // safe placeholder - we could base this on further discussion with ux/design team.
				'type'  => 'intro',
			];

			// secondary falls back by priority: review then site.
			if ($review !== '') {
				$secondary = ['label' => 'Read Reviews', 'url' => $review, 'type' => 'review'];
			} elseif ($site !== '') {
				$secondary = ['label' => 'Visit Website', 'url' => $site, 'type' => 'site'];
			}
		} else {
			// no intro CTA: primary is review then site.
			if ($review !== '') {
				$primary = ['label' => 'Read Reviews', 'url' => $review, 'type' => 'review'];
			} elseif ($site !== '') {
				$primary = ['label' => 'Visit Website', 'url' => $site, 'type' => 'site'];
			}

			// secondary: if primary is review and site exists, show site.
			if ($primary && $primary['type'] === 'review' && $site !== '') {
				$secondary = ['label' => 'Visit Website', 'url' => $site, 'type' => 'site'];
			}
		}

		return [
			'primary' => $primary,
			'secondary' => $secondary,
		];
	}
}

/**
 * Block wrapper attrs
 */
$anchor = isset($ctc_block['anchor']) && $ctc_block['anchor'] ? sanitize_title($ctc_block['anchor']) : '';
$block_id = $anchor !== '' ? $anchor : ($ctc_block['id'] ?? 'ctc-comparison-table-' . wp_rand());

$extra_class = '';
if (!empty($ctc_block['className'])) {
	$extra_class = ' ' . sanitize_html_class($ctc_block['className']);
}

$companies = get_field('companies');
if (!is_array($companies)) {
	$companies = [];
}

/**
 * Component authored fields (fields that can be edited)
 */
$title = trim((string) get_field('component_title'));
$sub   = trim((string) get_field('component_sub_text'));
$info  = trim((string) get_field('data_information'));
$disclaimer  = trim((string) get_field('disclaimer'));

?>
<section id="<?php echo esc_attr($block_id); ?>" class="ctc-cc<?php echo esc_attr($extra_class); ?>">
	<div class="ctc-cc__table" role="table" aria-label="<?php echo esc_attr__('Brokerage comparison', 'ctc'); ?>">

    <div class="ctc-cc__intro">
        <div class="ctc-cc__document">
            <span class="ctc-cc__documentIcon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                    <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                    <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                    <path d="M10 6h4"></path><path d="M10 10h4"></path>
                    <path d="M10 14h4"></path><path d="M10 18h4"></path>
                </svg>
            </span>
            <?php echo esc_html__('Brokerage comparison', 'ctc'); ?>
        </div>

        <?php if ($title !== '') : ?>
            <h2 class="ctc-cc__title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <?php if ($sub !== '') : ?>
            <p class="ctc-cc__sub"><?php echo esc_html($sub); ?></p>
        <?php endif; ?>

        <?php if ($info !== '') : ?>
            <div class="ctc-cc__info">
            <span class="ctc-cc__infoIcon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 16v-4"></path>
                    <path d="M12 8h.01"></path>
                </svg>
            </span>
            <?php echo esc_html($info); ?>
            </div>
        <?php endif; ?>
        </div>
        
		<div class="ctc-cc__head" role="rowgroup">
			<div class="ctc-cc__row ctc-cc__row--head" role="row"> 
            <div class="ctc-cc__cell ctc-cc__cell--company" role="columnheader"></div> 
				<div class="ctc-cc__cell ctc-cc__cell--rating" role="columnheader">Rating</div>
				<div class="ctc-cc__cell ctc-cc__cell--fee" role="columnheader">Listing fee</div>
				<div class="ctc-cc__cell ctc-cc__cell--exp" role="columnheader">Experience</div>
				<div class="ctc-cc__cell ctc-cc__cell--license" role="columnheader">License</div> 
                <div class="ctc-cc__cell ctc-cc__cell--actions" role="columnheader"> </div> 
			</div>
		</div>

		<div class="ctc-cc__body" role="rowgroup">
			<?php if (empty($companies)) : ?>
				<div class="ctc-cc__empty">
					<p><strong><?php echo esc_html__('No companies added yet.', 'ctc'); ?></strong></p>
					<p><?php echo esc_html__('Edit the block and add companies in the “Companies” repeater.', 'ctc'); ?></p>
				</div>
			<?php else : ?>
				<?php foreach ($companies as $row) :
					$name = trim((string) ($row['company_name'] ?? ''));
					if ($name === '') continue;

					$logo = trim((string) ($row['logo_url'] ?? ''));
					$market = trim((string) ($row['hq_market'] ?? ''));

					$rating = isset($row['average_rating']) && is_numeric($row['average_rating']) ? (float) $row['average_rating'] : null;
					$reviews_raw = $row['total_reviews'] ?? '';
					$reviews_fmt = ctc_format_reviews($reviews_raw);

					$price = trim((string) ($row['advertised_price'] ?? ''));
					$price_label = $price !== '' ? $price : 'Varies';

					$years = ctc_compute_years($row['start_year'] ?? null);
					$team = trim((string) ($row['team_size'] ?? ''));

					$license = trim((string) ($row['license'] ?? ''));
					$is_licensed = ($license !== '');

					$actions = ctc_pick_actions($row);
					$primary = $actions['primary'];
					$secondary = $actions['secondary'];

					$is_verified = $is_licensed;

					// Generate initials fallback when logo is missing or empty.
					$initials = '';
					foreach (preg_split('/\s+/', $name) as $part) {
						if ($part !== '') $initials .= mb_strtoupper(mb_substr($part, 0, 1));
						if (mb_strlen($initials) >= 2) break;
					} 
				?>
					<div class="ctc-cc__row" role="row">
						<!-- Company -->
						<div class="ctc-cc__cell ctc-cc__cell--company" role="cell">
							<div class="ctc-cc__company">
								<div class="ctc-cc__logo">
									<?php if ($logo !== '') : ?>
										<img src="<?php echo esc_url($logo); ?>" alt="" loading="lazy" decoding="async" />
									<?php else : ?>
										<span class="ctc-cc__logoFallback" aria-hidden="true"><?php echo esc_html($initials); ?></span>
									<?php endif; ?>
								</div>

								<div class="ctc-cc__companyMeta">
									<div class="ctc-cc__companyName">
										<?php echo esc_html($name); ?>
										<?php if ($is_verified) : ?>
											<span class="ctc-cc__verified" title="<?php echo esc_attr__('Verified', 'ctc'); ?>" aria-label="<?php echo esc_attr__('Verified', 'ctc'); ?>">
												<!-- check icon -->
												<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 1.7a8.3 8.3 0 1 0 0 16.6A8.3 8.3 0 0 0 10 1.7zm-1 11.1L5.9 9.7l1.2-1.2 2 2 3.9-3.9 1.2 1.2-5.2 5.0z"></path></svg>
											</span>
										<?php endif; ?>
									</div>

									<?php if ($market !== '') : ?>
										<div class="ctc-cc__companySub">
											<span class="ctc-cc__pin" aria-hidden="true">
												<svg viewBox="0 0 20 20" focusable="false"><path d="M10 1.5a6 6 0 0 0-6 6c0 4.4 6 11 6 11s6-6.6 6-11a6 6 0 0 0-6-6zm0 8.2a2.2 2.2 0 1 1 0-4.4 2.2 2.2 0 0 1 0 4.4z"></path></svg>
											</span>
											<?php echo esc_html($market); ?>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<!-- Rating -->
						<div class="ctc-cc__cell ctc-cc__cell--rating" role="cell">
							<?php if ($rating !== null && $rating > 0) : ?>
								<div class="ctc-cc__rating">
                                    <span class="ctc-cc__ratingValue"><?php echo esc_html(number_format($rating, 1)); ?></span>
									<?php echo ctc_render_stars($rating); ?>
									<?php if ($reviews_fmt !== '') : ?>
										<span class="ctc-cc__reviews">(<?php echo esc_html($reviews_fmt); ?> reviews)</span>
									<?php endif; ?>
									<span class="screen-reader-text">
										<?php
										$sr = sprintf('Rated %s out of 5', number_format($rating, 1));
										echo esc_html($sr);
										?>
									</span>
								</div>
							<?php else : ?>
								<div class="ctc-cc__muted"><?php echo esc_html__('No ratings yet', 'ctc'); ?></div>
							<?php endif; ?>
						</div>

						<div class="ctc-cc__stats">
						<!-- Listing fee -->
							<div class="ctc-cc__cell ctc-cc__cell--fee" role="cell">
								<div class="ctc-cc__fee">
									<div class="ctc-cc__feeValue"><?php echo esc_html($price_label); ?></div> 
								</div>
							</div>

							<!-- Experience -->
							<div class="ctc-cc__cell ctc-cc__cell--exp" role="cell">
								<div class="ctc-cc__exp">
									<div class="ctc-cc__expLine"> 
										<?php echo $years !== null ? esc_html($years . ' years') : '<span class="ctc-cc__muted">—</span>'; ?>
									</div>

									<?php if ($team !== '') : ?>
										<div class="ctc-cc__expLine ctc-cc__mutedLine"> 
											<?php echo esc_html($team . ' agents'); ?>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
						<!-- License -->
						<div class="ctc-cc__cell ctc-cc__cell--license" role="cell">
							<?php if ($is_licensed) : ?>
								<div class="ctc-cc__license">
									<span class="ctc-cc__licenseCheck" aria-label="Licensed">
                                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false">
                                            <path d="M7.6 13.3L3.9 9.6l1.4-1.4 2.3 2.3 6-6 1.4 1.4-7.4 7.4z"/>
                                        </svg>
                                    </span>
								</div>
							<?php else : ?>
								<span class="ctc-cc__muted">—</span>
							<?php endif; ?>
						</div>

						<!-- Actions -->
						<div class="ctc-cc__cell ctc-cc__cell--actions" role="cell">
							<div class="ctc-cc__actions">
								<?php if ($primary) : ?>
									<a
										class="ctc-cc__btn ctc-cc__btn--primary"
										href="<?php echo esc_url($primary['url']); ?>"
										<?php echo ($primary['type'] !== 'intro') ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
									>
										<span class="ctc-cc__btnIcon" aria-hidden="true">
											<?php if ($primary['type'] === 'intro') : ?>
												<svg viewBox="0 0 20 20" focusable="false"><path d="M3 3h14v9H7l-4 4V3zm3 4h8v2H6V7z"></path></svg>
											<?php else : ?>
												<svg viewBox="0 0 20 20" focusable="false"><path d="M12 2h6v6h-2V5.4l-6.9 6.9-1.4-1.4L14.6 4H12V2zM4 4h6v2H6v8h8v-4h2v6H4V4z"></path></svg>
											<?php endif; ?>
										</span>
										<?php echo esc_html($primary['label']); ?>
									</a>
								<?php else : ?>
									<span class="ctc-cc__btn ctc-cc__btn--disabled" aria-disabled="true">
										<?php echo esc_html__('No link available', 'ctc'); ?>
									</span>
								<?php endif; ?>

								<?php if ($secondary) : ?>
									<a
										class="ctc-cc__btn ctc-cc__btn--secondary"
										href="<?php echo esc_url($secondary['url']); ?>"
										target="_blank"
										rel="noopener noreferrer"
									>
										<?php echo esc_html($secondary['label']); ?>
										<span class="ctc-cc__external" aria-hidden="true">
											<svg viewBox="0 0 20 20" focusable="false"><path d="M12 2h6v6h-2V5.4l-6.9 6.9-1.4-1.4L14.6 4H12V2zM4 4h6v2H6v8h8v-4h2v6H4V4z"></path></svg>
										</span>
									</a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

    <?php if (!empty($disclaimer)) : ?>
    <div class="ctc-cc__disclaimer" role="note">
        <span class="ctc-cc__disclaimerLabel"><?php echo esc_html__('Disclaimer:', 'ctc'); ?></span>
        <span class="ctc-cc__disclaimerText"><?php echo esc_html($disclaimer); ?></span>
    </div>
    <?php endif; ?>
</section>
