<?php
/**
 * Event Email Behaviors.
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
		 * Event Email Behaviors.
		 *
		 * @package s2Member\Events
		 * @since 120318
		 *
		 * @assert ($GLOBALS[__NAMESPACE__])
		 */
		class event_email_behaviors extends event_behaviors_base
		{
			/**
			 * @var string Type of event behavior.
			 */
			public $type = 'email'; // For parent methods.

			/**
			 * Processes a specific event email behavior (by ID or name).
			 *
			 * @param integer|string $id_or_name The ID (or name) of an event email behavior.
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
					$behavior = clone $behavior; // Need a shallow clone.

					if($behavior->status !== 'active') return; // Not even active?

					if(isset($meta_vars['user'])) // For IDEs; properties/methods.
						$meta_vars['user'] = $this->©user_utils->which($meta_vars['user']);

					$behavior          = $this->©strings->ireplace_codes_deep($behavior, $meta_vars, $vars, TRUE, FALSE, ', ');
					$behavior->message = $this->©php->evaluate($behavior->message, $meta_vars + $vars);

					if(isset($behavior->headers)) $behavior->headers = maybe_unserialize($behavior->headers);
					if(isset($behavior->headers) && !is_array($behavior->headers)) // Or a line-delimited list of headers.
						$behavior->headers = preg_split('/['."\r\n".']+/', $behavior->headers, NULL, PREG_SPLIT_NO_EMPTY);

					if(isset($behavior->attachments)) $behavior->attachments = maybe_unserialize($behavior->attachments);
					if(isset($behavior->attachments) && !is_array($behavior->attachments)) // Or a line-delimited list of attachments.
						$behavior->attachments = preg_split('/['."\r\n".']+/', $behavior->attachments, NULL, PREG_SPLIT_NO_EMPTY);

					if(!$behavior->from_addr || !$behavior->recipients || !$behavior->subject || !$behavior->message)
						return; // Missing component(s); CANNOT send in this case.

					if(!$this->©mail->parse_emails_deep($behavior->recipients)) return; // NO recipients.

					$this->©mail->send(array('from_name'   => (string)$behavior->from_name, 'from_addr' => $behavior->from_addr,
					                         'recipients'  => $behavior->recipients, 'headers' => (array)$behavior->headers,
					                         'subject'     => $behavior->subject, 'message' => $behavior->message,
					                         'attachments' => (array)$behavior->attachments));
				}
		}
	}