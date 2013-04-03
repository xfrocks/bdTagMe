<?php

class bdTagMe_Listener {
	public static function load_class_datawriter($class, array &$extend) {
		if ($class == 'XenForo_DataWriter_DiscussionMessage_Post') {
			$extend[] = 'bdTagMe_DataWriter_DiscussionMessagePost';
		}
	}
}