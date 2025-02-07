<?php

namespace isrdxv\practice\webhook;

use libs\discord\{
  Webhook,
  Message,
  Embed
};

use isrdxv\practice\Loader;

class WebhookManager
{
  private Loader $loader;
  
  private Webhook $user;
  
  private Webhook $staff;
  
  public function __construct(Loader $loader)
  {
    $this->loader = $loader;
    $this->user = new Webhook($loader->getConfig()->get("discord-logs")["user"]);
    $this->staff = new Webhook($loader->getConfig()->get("discord-logs")["staff"]);
  }
  
  public function getWebhookUser(): Webhook
  {
    return $this->user;
  }
  
  public function getWebhookStaff(): Webhook
  {
    return $this->staff;
  }
  
  public function sendMessage(string $type, string $content = ""): void
  {
    if ($type === "user") {
      $msg = new Message();
      $msg->setUsername($this->loader->getConfig()->get("discord")["username"]);
      $msg->setAvatarURL($this->loader->getConfig()->get("discord")["avatarURL"]);
      $msg->setContent($content);
      $this->user->send($msg);
    } else {
      $msg = new Message();
      $msg->setUsername($this->loader->getConfig()->get("discord")["username"]);
      $msg->setAvatarURL($this->loader->getConfig()->get("discord")["avatarURL"]);
      $msg->setContent($content);
      $this->staff->send($msg);
    }
  }
  
  public function sendEmbed(string $type): void
  {
    if ($type === "user") {
      
    }
  }
  
}
