<?php

declare(strict_types=1);

namespace peels\flashmsg;

use orange\framework\Container;
use peels\session\SessionInterface;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\EventInterface;
use orange\framework\interfaces\OutputInterface;

class Flashmsg implements FlashMsgInterface
{
    private FlashMsgInterface $instance;

    protected array $config = [];
    protected array $messages = [];

    protected SessionInterface $session;
    protected ?EventInterface $event;
    protected OutputInterface $output;
    protected DataInterface $data;

    public function __construct(array $config, SessionInterface $session, OutputInterface $output, ?DataInterface $data = null)
    {
        $this->config = mergeDefaultConfig($config, __DIR__ . '/config/flashmsg.php');
        $this->session = $session;
        $this->output = $output;
        $this->data = $data;

        if (Container::has('events')) {
            $this->event = Container::get('events');
        }

        /* are there any messages in cold storage? */
        if (is_array($previousMessages = $this->session->get($this->config['session msg key']))) {
            $this->messages = $previousMessages;

            $this->session->remove($this->config['session msg key']);
        }

        /* set the view variable for this page */
        $this->refreshData();
    }

    public static function getInstance(array $config, SessionInterface $session, OutputInterface $output, ?DataInterface $data = null): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config, $session, $output, $data);
        }

        return self::$instance;
    }

    /**
     * add a flash msg
     */
    public function msg(string $msg = '', string $type = null): self
    {
        $type = ($type) ?? $this->config['default type'];

        /* is this type sticky? - use names not colors - colors support for legacy code */
        $sticky = in_array($type, $this->config['sticky types']);

        if ($this->event) {
            /* trigger a event incase they need to do something */
            $this->event->trigger('flash.msg', $msg, $type, $sticky);
        }

        $this->messages[md5(trim($type . $msg))] = ['msg' => trim($msg), 'type' => $type, 'sticky' => $sticky];

        /* put in view variable incase they want to use it on this page */
        $this->refreshData();

        return $this;
    }

    /**
     * add multiple flash msgs
     */
    public function msgs(array $array, string $type = null): self
    {
        $type = ($type) ?? $this->config['default type'];

        foreach ($array as $a => $b) {
            if (is_numeric($a)) {
                $this->msg($b, $type);
            } else {
                $this->msg($a, $b);
            }
        }

        return $this;
    }

    /**
     * if redirect @ we are automatically redirected to the HTTP_REFERER
     */
    public function redirect(string $redirect): void
    {
        // if it starts with @ then use the referer
        if (substr($redirect, 0, 1) == '@') {
            /* where did we come from? */
            $redirect = $this->config['http referer'];
        }

        // store this in a session variable for redirect
        $this->session->set($this->config['session msg key'], $this->messages);

        $this->output->redirect($redirect);
    }

    /**
     * return all of the messages
     *
     * detailed in array or just array of messages
     */
    public function getMessages(bool $detailed = false): array
    {
        $messages = array_values($this->messages);

        return ($detailed) ? ['messages' => $messages, 'count' => count($this->messages), 'initial_pause' => $this->config['initial pause'], 'pause_for_each' => $this->config['pause for each']] : $messages;
    }

    /* set the view variable stored in data (for view) to something other that default */
    protected function refreshData(): self
    {
        if ($this->data) {
            $this->data[$this->config['view variable']] = $this->getMessages(true);
        }

        return $this;
    }

    public function __debugInfo(): array
    {
        return [
            'config' => $this->config,
            'messages' => $this->messages,
        ];
    }
}
