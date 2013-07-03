<?php
/**
 * Event Behaviors.
 *
 * Copyright: © 2012 (coded in the USA)
 * {@link http://www.websharks-inc.com WebSharks™}
 *
 * @author JasWSInc
 * @package s2Member\Events
 * @since 120318
 *
 * @TODO Create unsubscribe link generator.
 */
namespace s2member
	{
		if(!defined('WPINC'))
			exit('Do NOT access this file directly: '.basename(__FILE__));

		/**
		 * Event Behaviors.
		 *
		 * @package s2Member\Events
		 * @since 120318
		 *
		 * @assert ($GLOBALS[__NAMESPACE__])
		 */
		class event_behaviors extends framework
		{
			/**
			 * Gets a specific event behavior.
			 *
			 * @param integer $id The ID of an event behavior.
			 *
			 * @return null|object An event behavior object, else NULL.
			 *
			 * @throws exception If invalid types are passed through arguments list.
			 * @throws exception If ``$id`` is empty.
			 */
			public function get($id)
				{
					$this->check_arg_types('integer:!empty', func_get_args());

					$behaviors = $this->get_all();

					if(isset($behaviors['by_id'][$id]))
						return $behaviors['by_id'][$id];

					return NULL; // Default return value.
				}

			/**
			 * Gets all behaviors for a specific event handler (by ID or name).
			 *
			 * @param integer|string $event_handler_id_or_name The ID (or name) of an event handler.
			 *
			 * @return array An array of event behaviors; else an empty array on failure.
			 *
			 * @throws exception If invalid types are passed through arguments list.
			 * @throws exception If ``$event_handler_id_or_name`` is empty.
			 */
			public function for_($event_handler_id_or_name)
				{
					$this->check_arg_types(array('integer:!empty', 'string:!empty'), func_get_args());

					$behaviors = $this->get_all();

					if(!($handler = $this->©event_handler->get($event_handler_id_or_name))) // Missing?
						throw $this->©exception($this->method(__FUNCTION__).'#handler_missing', get_defined_vars(),
						                        sprintf($this->i18n('Missing event handler ID/name: `$1%s`.'), $event_handler_id_or_name));

					if(isset($behaviors['by_event_handler_id'][$handler->ID]))
						return $behaviors['by_event_handler_id'][$handler->ID];

					return array(); // Default return value.
				}

			/**
			 * Gets all active behaviors for a specific event handler (by ID or name).
			 *
			 * @param integer|string $event_handler_id_or_name The ID (or name) of an event handler.
			 *
			 * @param array          $meta_vars Meta vars/data specific to this event; see {@link events::trigger()}.
			 *
			 * @return array An array of active event behaviors; else an empty array on failure.
			 *
			 * @throws exception If invalid types are passed through arguments list.
			 * @throws exception If ``$event_handler_id_or_name`` is empty.
			 * @throws exception If ``$meta_vars`` is empty.
			 */
			public function active_for($event_handler_id_or_name, $meta_vars)
				{
					$this->check_arg_types(array('integer:!empty', 'string:!empty'), 'array:!empty', func_get_args());

					$behaviors        = $this->get_all();
					$active_behaviors = array(); // Initialize.

					if(!($handler = $this->©event_handler->get($event_handler_id_or_name))) // Missing?
						throw $this->©exception($this->method(__FUNCTION__).'#handler_missing', get_defined_vars(),
						                        sprintf($this->i18n('Missing event handler ID/name: `$1%s`.'), $event_handler_id_or_name));

					if(!isset($behaviors['by_event_handler_id'][$handler->ID])) return array(); // No behaviors?

					$ids = $this->©event->meta_var_ids($handler->ID, $meta_vars); // IDs from ``$meta_vars``.

					foreach($behaviors['by_event_handler_id'][$handler->ID] as $_behavior)
						{
							if($_behavior->status === 'deleted') continue; // Deleted?

							if($_behavior->behavior_type_id <= 1) continue; // Not applicable?
							// Behavior IDs `0` and `1` indicate NO behavior; or a default behavior.
							// Handlers do NOT support default behaviors; site owners choose.

							$_status = // Behavior "statuses" take precedence.
								$this->check_statuses_for($handler->ID, $meta_vars, $_behavior->ID);
							if(!$_status) $_status = $_behavior->status;
							if($_status !== 'active') continue;

							$active_behaviors[$_behavior->ID] = $_behavior;
						}
					unset($_behavior, $_status); // Housekeeping.

					return $active_behaviors;
				}

			/**
			 * Checks statuses for an event behavior.
			 *
			 * @param integer|string $event_handler_id_or_name The ID (or name) of an event handler.
			 *
			 * @param array          $meta_vars Meta vars/data specific to this event; see {@link events::trigger()}.
			 *
			 * @param integer        $id The ID of an event behavior.
			 *
			 * @return string Current forced status; else an empty string.
			 *
			 * @throws exception If invalid types are passed through arguments list.
			 * @throws exception If ``$event_handler_id_or_name`` is empty.
			 * @throws exception If ``$id`` is empty for some reason.
			 * @throws exception If ``$meta_vars`` is empty.
			 */
			public function check_statuses_for($event_handler_id_or_name, $meta_vars, $id)
				{
					$this->check_arg_types(array('integer:!empty', 'string:!empty'), 'array:!empty', 'integer:!empty', func_get_args());

					if(!($handler = $this->©event_handler->get($event_handler_id_or_name))) // Missing?
						throw $this->©exception($this->method(__FUNCTION__).'#handler_missing', get_defined_vars(),
						                        sprintf($this->i18n('Missing event handler ID/name: `$1%s`.'), $event_handler_id_or_name));

					$ids = $this->©event->meta_var_ids($handler->ID, $meta_vars); // IDs from ``$meta_vars``.

					consider_user: // We ALWAYS consider the user triggering this event.

					if(!isset($meta_vars['user'])) // There is NO user in this scenario (rare).
						$where['user'][] = // Search for global statuses (e.g. impacting ALL; even NO user).
							"(`event_behavior_statuses`.`user_id` <= '-2' AND `event_behavior_statuses`.`user_id` IS NOT NULL".
							" AND (`event_behavior_statuses`.`user_passtag_id` <= '0' OR `event_behavior_statuses`.`user_passtag_id` IS NULL))";

					else $where['user'][] = // Search for global statuses (e.g. impacting ALL users).
						"((`event_behavior_statuses`.`user_id` <= '-1' OR `event_behavior_statuses`.`user_id` IS NULL)".
						" AND (`event_behavior_statuses`.`user_passtag_id` <= '0' OR `event_behavior_statuses`.`user_passtag_id` IS NULL))";

					if($ids['user_id']) $where['user'][] = // Search for this user's ID also (e.g. user-specific).
						"(`event_behavior_statuses`.`user_id` = '".$this->©string->esc_sql((string)$ids['user_id'])."'".
						" AND `event_behavior_statuses`.`user_id` IS NOT NULL".
						" AND `event_behavior_statuses`.`user_id` > '0')";

					if($ids['user_passtag_ids']) $where['user'][] = // OR, a user w/ any of these passtag IDs.
						"(`event_behavior_statuses`.`user_passtag_id` IN(".$this->©db_utils->comma_quotify($ids['user_passtag_ids']).")".
						" AND `event_behavior_statuses`.`user_passtag_id` IS NOT NULL".
						" AND `event_behavior_statuses`.`user_passtag_id` > '0')";

					$where['user'] = '('.implode(' OR ', $where['user']).')'; // Any/OR :-)

					check_status: // Target point. Check statuses for this event behavior ID.

					$query = // Precedence given to any user-specific statuses.
						"SELECT".
						" `event_behavior_statuses`.`status`".

						" FROM".
						" `".$this->©string->esc_sql($this->©db_tables->get('event_behavior_statuses'))."` AS `event_behavior_statuses`".

						" WHERE ".
						" `event_behavior_statuses`.`event_behavior_id` = '".$this->©string->esc_sql((string)$id)."'".
						" AND `event_behavior_statuses`.`event_behavior_id` IS NOT NULL".
						" AND `event_behavior_statuses`.`event_behavior_id` > '0'".

						' AND '.$where['user']. // Any/OR (including global statuses).

						" ORDER BY `event_behavior_statuses`.`user_id` DESC,". // Give user IDs precedence (-1, -2 last).
						"     `event_behavior_statuses`.`user_passtag_id` DESC". // Then user passtag IDs.

						" LIMIT 1"; // We want the first result only (based on ORDER BY in the query).

					return (string)$this->©db->get_var($query);
				}

			/**
			 * Gets all event behaviors.
			 *
			 * @return array All event behaviors.
			 */
			public function get_all()
				{
					$db_cache_key = $this->method(__FUNCTION__);

					if(is_array($cache = $this->©db_cache->get($db_cache_key)))
						return $cache; // Already cached these.

					$event_behaviors = array();

					$query =
						"SELECT".
						" `behavior_types`.`type` AS `behavior_type`,".
						" `event_behaviors`.*".

						" FROM".
						" `".$this->©string->esc_sql($this->©db_tables->get('behavior_types'))."` AS `behavior_types`,".
						" `".$this->©string->esc_sql($this->©db_tables->get('event_behaviors'))."` AS `event_behaviors`".

						" WHERE ".
						" `event_behaviors`.`behavior_type_id` = `behavior_types`.`ID`".
						" AND `event_behaviors`.`behavior_type_id` IS NOT NULL".
						" AND `event_behaviors`.`behavior_type_id` >= '0'".

						" AND `behavior_types`.`type` IS NOT NULL".
						" AND `behavior_types`.`type` != ''".

						" AND `event_behaviors`.`event_handler_id` IS NOT NULL".
						" AND `event_behaviors`.`event_handler_id` > '0'".

						" ORDER BY `event_behaviors`.`order` ASC";

					if(is_array($results = $this->©db->get_results($query, OBJECT)))
						{
							$_default_behavior_type_id = $this->©behavior_type->id('default');

							foreach($this->©db_utils->typify_results_deep($results) as $_result)
								{
									if($_result->behavior_type_id === $_default_behavior_type_id)
										{
											$_result->behavior_type    = 'none'; // No behavior.
											$_result->behavior_type_id = 0; // There is no behavior.
										}
									$event_behaviors['by_id'][$_result->ID]                                           = $_result;
									$event_behaviors['by_behavior_type_id'][$_result->behavior_type_id][$_result->ID] =& $event_behaviors['by_id'][$_result->ID];
									$event_behaviors['by_behavior_type'][$_result->behavior_type][$_result->ID]       =& $event_behaviors['by_id'][$_result->ID];
									$event_behaviors['by_event_handler_id'][$_result->event_handler_id][$_result->ID] =& $event_behaviors['by_id'][$_result->ID];
								}
							unset($_default_behavior_type_id, $_result); // Just a little housekeeping.
						}
					return $this->©db_cache->update($db_cache_key, $event_behaviors);
				}
		}
	}