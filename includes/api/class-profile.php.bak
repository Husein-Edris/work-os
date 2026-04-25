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

			$headline = get_field( 'about_hero_subtitle', $pid ) ?: 'WordPress & PHP Developer';
			$summary  = get_field( 'about_summary', $pid )
				?: get_field( 'about_intro_text', $pid )
				?: '';

			$items = get_field( 'experience_items', $pid );
			if ( is_array( $items ) ) {
				foreach ( $items as $item ) {
					$tech = array();
					if ( ! empty( $item['technologies'] ) ) {
						foreach ( (array) $item['technologies'] as $t ) {
							if ( is_object( $t ) ) {
								$tech[] = $t->post_title;
							} elseif ( is_numeric( $t ) ) {
								$post = get_post( (int) $t );
								if ( $post ) $tech[] = $post->post_title;
							} else {
								$tech[] = (string) $t;
							}
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

		// Technical skills from the 'tech' CPT
		$tech_skill_posts = get_posts( array(
			'post_type'      => 'tech',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		$tech_skills = array_map( fn( $p ) => $p->post_title, $tech_skill_posts );

		// Static education — update via Settings once ACF fields are added
		$education = array(
			array(
				'degree'      => 'Diploma in Full Stack Web Development',
				'institution' => 'Instant Software Solution',
				'period'      => 'Oct 2021 – Aug 2022',
			),
			array(
				'degree'      => 'Ausbildung Medienfachmann (Apprenticeship)',
				'institution' => 'bobdo.com GmbH · Landesberufsschule Bregenz 2',
				'period'      => '2019 – 2021',
			),
		);

		$languages = array(
			array( 'language' => 'Kurdish', 'level' => 'Native' ),
			array( 'language' => 'German',  'level' => 'C1 – Fluent' ),
			array( 'language' => 'English', 'level' => 'C1 – Fluent' ),
		);

		$projects = self::get_projects();

		return rest_ensure_response( array(
			'name'        => get_bloginfo( 'name' ) ?: 'Edris Husein',
			'headline'    => $headline,
			'location'    => 'Dornbirn, Österreich',
			'email'       => get_option( 'admin_email' ),
			'summary'     => $summary,
			'experience'  => $experience,
			'skills'      => $skills,
			'tech_skills' => $tech_skills,
			'projects'    => $projects,
			'education'   => $education,
			'languages'   => $languages,
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
					if ( is_object( $t ) ) {
						$tech[] = $t->post_title;
					} elseif ( is_numeric( $t ) ) {
						$post_obj = get_post( (int) $t );
						if ( $post_obj ) $tech[] = $post_obj->post_title;
					} else {
						$tech[] = (string) $t;
					}
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
