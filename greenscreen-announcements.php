<?php
/*
Plugin Name: Greenscreen Announcements
Plugin URI: http://www.indyhall.org/
Description: Greenscreen Announcements is a Wordpress plugin to display announcements on a TV
Version: 0.1
Author: Chris Morrell
Author URI: http://cmorrell.com
License: GPL2
*/

if (!class_exists('acf')) {
	add_action('admin_notices', function() {
		?>
    	<div class="error">
        	<p>You must install the <a href="http://www.advancedcustomfields.com/" target="_blank">Advanced 
        		Custom Fields</a> plugin to use the <strong>Greenscreen Announcements</strong> plugin.</p>
    	</div>
    	<?php
	});
	return;
}


add_action('wp_ajax_greenscreen_announcements', 'render_greenscreen_announcements');
add_action('wp_ajax_nopriv_greenscreen_announcements', 'render_greenscreen_announcements');

function render_greenscreen_announcements() {
	$colors = array(
		'#00A0B0',
		'#6A4A3C',
		'#CC333F',
		'#13BA93',
		'#EB6841',
		'#27384A',
		'#85556E',
	);

	$query = new WP_Query(array(
		'category_name' => 'announcements', // TODO: Abstract this
		'meta_query' => array(
			array(
				'key' => 'greenscreen',
				'compare' => 'EXISTS'
			),
			array(
				'key' => 'greenscreen',
				'compare' => '!=',
				'value' => ''
			)
		)
	));

	// X-Frame-Options Header
	if (function_exists('header_remove')) {
		header_remove('X-Frame-Options');
	}

	?>

	<!DOCTYPE html>
	<html>
		<head>
			<meta http-equiv="refresh" content="600" />
			<link href="http://fonts.googleapis.com/css?family=Slabo+27px" rel="stylesheet" type="text/css" />

			<style type="text/css">
			*, *:before, *:after {
				box-sizing: border-box;
			}
			html, body {
				height: 100%;
				margin: 0;
				overflow: hidden;
				background: <?php echo $colors[0]; ?>;
				color: #fff;
			}
			p {
				margin: 1em 0 0 0;
				padding: 0;
			}
			p:first-child { 
				margin: 0;
			}
			a {
				color: #fff;
			}
			.slide {
				position: absolute;
				width: 100%;
				height: 100%;
				top: 0;
				left: 0;
				opacity: 0;
				transition: opacity 1s ease-in-out;
				transform-style: preserve-3d;
			}
			.slide.visible {
				opacity: 1;
			}
			.announcement {
				position: relative;
				top: 50%;
				transform: translateY(-50%);
				padding: 50px;
				text-align: center;
				font-family: "Slabo 27px", sans-serif;
				text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.4);
			}
			</style>
		</head>
		<body>

			<div id="greenscreen">
				<?php
				$colorCount = count($colors);
				$row = 0;

				while ($query->have_posts()):
				$post = $query->next_post();
				$field = get_field('greenscreen', $post->ID);

				// Check expiration
				$expires = intval(get_field('greenscreen_expires', $post->ID)) / 1000;
				if ($expires && $expires <= time()) {
					continue;
				}

				// Set up color
				$color = $colors[$row++ % $colorCount];
				?>

				<div class="slide" style="background-color: <?php echo $color; ?>">
					<div class="announcement">
						<?php echo $field; ?>
					</div>
				</div>

				<?php endwhile; ?>
			</div>

			<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>

			<script type="text/javascript">
			$(function() {
				var targetHeight = $(window).outerHeight();
				var $slides = $('.slide');
				var slideCount = $slides.length;
				var currentSlide = 0;
				var processed = 0;

				function process($a, lastSize) {
					var thisSize = lastSize + 2;
					$a.css('font-size', thisSize + 'px');
					if (thisSize >= 200 || $a.outerHeight() > targetHeight) {
						$a.css('font-size', lastSize + 'px');
						processed++;
						run();
						return;
					}
					setTimeout(function() {
						process($a, thisSize);
					}, 0);
				}

				function rotateSlides() {
					$('.visible').removeClass('visible');
					$($slides[currentSlide]).addClass('visible');

					// Move forward
					currentSlide++;
					if (currentSlide >= slideCount) {
						currentSlide = 0;
					}
				}

				function run() {
					if (processed < slideCount) {
						return;
					}

					rotateSlides();
					var slideInterval = setInterval(rotateSlides, 10000);
				}

				$('.announcement').each(function() {
					process($(this), 20);
				});
			});
			</script>

		</body>
	</html>

	<?php

	// Done
	exit;
}