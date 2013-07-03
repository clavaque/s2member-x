<?php
/**
 * Event Status Behaviors.
 *
 * Copyright: © 2012 (coded in the USA)
 * {@link http://www.websharks-inc.com WebSharks™}
 *
 * @author JasWSInc
 * @package s2Member\Events
 * @since 120318
 */
namespace s2member
	{
		if(!defined('WPINC'))
			exit('Do NOT access this file directly: '.basename(__FILE__));

		/**
		 * Event Status Behaviors.
		 *
		 * @package s2Member\Events
		 * @since 120318
		 *
		 * @assert ($GLOBALS[__NAMESPACE__])
		 */
		class event_status_behaviors extends event_behaviors_base
		{
			/**
			 * @var string Type of event behavior.
			 */
			public $type = 'status'; // For parent methods.

			/**
			 * Processes a specific event status behavior (by ID or name).
			 *
			 * @param integer|string $id_or_name The ID (or name) of an event status behavior.
			 *
			 * @param array          $meta_vars Meta vars/data specific to this event; see {@link events::trigger()}.
			 *
			 * @param array          $vars Variables defined in the scope of the calling routine.
			 *
			 * @throws exception If invalid types are passed through arguments list.
			 * @throws exception If ``$id_or_name`` is empty for some reason.
			 * @throws exception If ``$meta_vars`` is empty.
			 */
			public function process($id_or_name, $meta_vars, $vars)
				{
					$this->check_arg_types(array('integer:!empty', 'string:!empty'), 'array:!empty', 'array', func_get_args());

					if(!($behavior = $this->get($id_or_name)))
						throw $this->©exception( // Should NOT happen.
							$this->method(__FUNCTION__).'#behavior_missing', get_defined_vars(),
							sprintf($this->i18n('Missing event behavior ID/name: `$1%s`.'), $id_or_name)
						);
					if(!($behavior_type = $this->©behavior_type->get($behavior->behavior_type_id)))
						throw $this->©exception($this->method(__FUNCTION__).'#invalid_behavior_type', get_defined_vars(),
						                        sprintf($this->i18n('Invalid behavior type: `$1%s`.'), $behavior->behavior_type_id)
						);
					$behavior = clone $behavior; // Need a shallow clone.

					if($behavior->status !== 'active') return; // Not even active?

					if(!$behavior->this_event_behavior_id) return; // MUST have.

					if(isset($meta_vars['user'])) // For IDEs; properties/methods.
						$meta_vars['user'] = $this->©user_utils->which($meta_vars['user']);

					$behavior->user_id         = (integer)$behavior->user_id;
					$behavior->user_passtag_id = 0; // Initialize property.

					if(!$behavior->user_id) // NULL or 0 = event user (if there is one).
						{
							if(isset($meta_vars['user']) && $meta_vars['user']->has_id())
								$behavior->user_id = $meta_vars['user']->ID;

							else if(isset($meta_vars['user']) && $meta_vars['user']->passtag_ids())
								$behavior->user_passtag_id = $this->©array->first($meta_vars['user']->passtag_ids());
						}
					if(!$behavior->user_id && !$behavior->user_passtag_id)
						return; // Nothing to identify the user.

					switch($behavior_type->type) // Based on behavior type.
					{
						case 'activate_status': // Insert/replace; set `active` status.

								$this->©db->replace($this->©db_table->get('event_behavior_statuses'),
								                    array('event_behavior_id' => $behavior->this_event_behavior_id,
								                          'user_id'           => $behavior->user_id,
								                          'user_passtag_id'   => $behavior->user_passtag_id,
								                          'status'            => 'active',
								                          'time'              => time()));

								break; // Break switch handler.

						case 'deactivate_status': // Insert/replace; set `inactive` status.

								$this->©db->replace($this->©db_table->get('event_behavior_statuses'),
								                    array('event_behavior_id' => $behavior->this_event_behavior_id,
								                          'user_id'           => $behavior->user_id,
								                          'user_passtag_id'   => $behavior->user_passtag_id,
								                          'status'            => 'inactive',
								                          'time'              => time()));

								break; // Break switch handler.

						default: // There is NO default behavior.
							break; // Break switch handler.

					}
				}
		}
	}