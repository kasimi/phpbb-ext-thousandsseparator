<?php declare(strict_types=1);

/**
 *
 * Thousands Separator. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019, kasimi, https://kasimi.net
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace kasimi\thousandsseparator\event;

use phpbb\config\config;
use phpbb\event\data;
use phpbb\language\language;
use phpbb\template\template;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var language */
	protected $language;

	/** @var config */
	protected $config;

	/** @var template */
	protected $template;

	public function __construct(
		language $language,
		config $config,
		template $template
	)
	{
		$this->language	= $language;
		$this->config	= $config;
		$this->template	= $template;
	}

	public static function getSubscribedEvents(): array
	{
		return [
			'core.user_setup_after'						=> 'user_setup_after',
			'core.index_modify_page_title'				=> 'index_modify_page_title',
			'core.search_modify_tpl_ary'				=> 'search_modify_tpl_ary',
			'core.viewforum_modify_topicrow'			=> 'viewforum_modify_topicrow',
			'core.viewtopic_cache_user_data'			=> 'viewtopic_cache_user_data',
			'core.display_forums_modify_template_vars'	=> 'display_forums_modify_template_vars',
		];
	}

	protected function format_number($number): string
	{
		$decimal_sep = $this->language->lang('DECIMAL_SEP');
		$thousands_sep = $this->language->lang('THOUSANDS_SEP');
		return number_format((int) $number, 0, $decimal_sep, $thousands_sep);
	}

	public function user_setup_after(): void
	{
		$this->language->add_lang('common', 'kasimi/thousandsseparator');
	}

	public function index_modify_page_title(): void
	{
		$this->template->assign_vars([
			'TOTAL_POSTS'	=> $this->language->lang('TOTAL_POSTS_COUNT', $this->format_number($this->config['num_posts'])),
			'TOTAL_TOPICS'	=> $this->language->lang('TOTAL_TOPICS', $this->format_number($this->config['num_topics'])),
			'TOTAL_USERS'	=> $this->language->lang('TOTAL_USERS', $this->format_number($this->config['num_users'])),
		]);
	}

	public function search_modify_tpl_ary(data $event): void
	{
		$event['tpl_ary'] = array_merge($event['tpl_ary'], [
			'TOPIC_REPLIES'	=> $this->format_number($event['replies']),
			'TOPIC_VIEWS'	=> $this->format_number($event['row']['topic_views']),
		]);
	}

	public function viewforum_modify_topicrow(data $event): void
	{
		$event['topic_row'] = array_merge($event['topic_row'], [
			'REPLIES'	=> $this->format_number($event['topic_row']['REPLIES']),
			'VIEWS'		=> $this->format_number($event['row']['topic_views']),
		]);
	}

	public function viewtopic_cache_user_data(data $event): void
	{
		$event['user_cache_data'] = array_merge($event['user_cache_data'], [
			'posts' => $this->format_number($event['row']['user_posts']),
		]);
	}

	public function display_forums_modify_template_vars(data $event): void
	{
		$new_forum_row = [
			'TOPICS' => $this->format_number($event['row']['forum_topics']),
		];

		$l_post_click_count = $event['row']['forum_type'] == FORUM_LINK ? 'CLICKS' : 'POSTS';
		$post_click_count = $event['forum_row'][$l_post_click_count];

		if ($post_click_count)
		{
			$new_forum_row[$l_post_click_count] = $this->format_number($post_click_count);
		}

		$event['forum_row'] = array_merge($event['forum_row'], $new_forum_row);
	}
}
