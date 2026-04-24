<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkOS_Profile {

	public static function get() {
		if ( ! function_exists( 'get_field' ) ) {
			return new WP_Error( 'acf_missing', 'ACF plugin is required', array( 'status' => 500 ) );
		}

		$about_page = self::find_about_page();

		$experience = array();
		$skills     = array();
		$summary    = '';
		$headline   = '';

		if ( $about_page ) {
			$pid = $about_page->ID;

			$headline = get_field( 'about_hero_subtitle', $pid ) ?: get_bloginfo( 'description' );
			$summary  = get_field( 'about_hero_subtitle', $pid ) ?: '';

			$items = get_field( 'experience_items', $pid );
			if ( is_array( $items ) ) {
				foreach ( $items as $item ) {
					$tech = array();
					if ( ! empty( $item['technologies'] ) ) {
						foreach ( (array) $item['technologies'] as $t ) {
							$tech[] = is_object( $t ) ? $t->post_title : (string) $t;
						}
					}
					$experience[] = array(
						'company'     => $item['company_name'] ?? '',
						'role'        => $item['position'] ?? '',
						'period'      => $item['duration'] ?? '',
						'description' => wp_strip_all_tags( $item['description'] ?? '' ),
						'tech'        => $tech,
					);
				}
			}

			$skill_posts = get_field( 'selected_skills', $pid );
			if ( is_array( $skill_posts ) ) {
				foreach ( $skill_posts as $s ) {
					$skills[] = is_object( $s ) ? $s->post_title : (string) $s;
				}
			}
		}

		$projects = self::get_projects();

		return rest_ensure_response( array(
			'name'       => get_bloginfo( 'name' ) ?: 'Edris Husein',
			'headline'   => 'WordPress & PHP Developer',
			'location'   => 'Dornbirn, Österreich',
			'email'      => get_option( 'admin_email' ),
			'summary'    => $summary,
			'experience' => $experience,
			'skills'     => $skills,
			'projects'   => $projects,
		) );
	}

	private static function find_about_page() {
		foreach ( array( 'about-me', 'about' ) as $slug ) {
			$pages = get_posts( array(
				'post_type'      => 'page',
				'name'           => $slug,
				'posts_per_page' => 1,
				'post_status'    => 'publish',
			) );
			if ( ! empty( $pages ) ) {
				return $pages[0];
			}
		}
		return null;
	}

	private static function get_projects() {
		$posts = get_posts( array(
			'post_type'      => 'project',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		$projects = array();
		foreach ( $posts as $post ) {
			$tech = array();
			$raw_tech = get_field( 'tech_stack', $post->ID );
			if ( is_array( $raw_tech ) ) {
				foreach ( $raw_tech as $t ) {
					$tech[] = is_object( $t ) ? $t->post_title : (string) $t;
				}
			}

			$features = array();
			$raw_features = get_field( 'key_features', $post->ID );
			if ( is_array( $raw_features ) ) {
				foreach ( $raw_features as $f ) {
					$features[] = array(
						'title'       => $f['title'] ?? '',
						'description' => $f['description'] ?? '',
					);
				}
			}

			$projects[] = array(
				'id'        => $post->ID,
				'title'     => $post->post_title,
				'excerpt'   => get_the_excerpt( $post ),
				'challenge' => wp_strip_all_tags( get_field( 'challenge', $post->ID ) ?: '' ),
				'solution'  => wp_strip_all_tags( get_field( 'solution', $post->ID ) ?: '' ),
				'features'  => $features,
				'tech'      => $tech,
				'live_url'  => get_field( 'live_site', $post->ID ) ?: '',
				'github'    => get_field( 'github', $post->ID ) ?: '',
				'date'      => get_the_date( 'Y-m-d', $post ),
			);
		}

		return $projects;
	}
}
