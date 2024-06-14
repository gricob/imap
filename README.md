# Quick start

## Install
```shell
composer require gricob/imap
```

## Usage
```php
$client = \Gricob\IMAP\Client::create(
    new \Gricob\IMAP\Configuration(
        transport: 'ssl',
        host: 'imap.example.com',
        port: 993,
        timeout: 60,
        verifyPeer: true,
        verifyPeerName: true,
        allowSelfSigned: false,
        useUid: true,
    )
);

$client->logIn('username', 'password');

// List available mailbox
$mailboxes = $client->list();

// Select an specific mailbox
$client->select($mailboxes[0]->name);

// Fetch message by sequence number or uid (depends on useUid configuration)
$message = $client->fetch(1);

// Or search messages by criteria
$messages = $client->search()
    ->since(new DateTime('yesterday'))
    ->not()->header('In-Reply-To'))
    ->get();

```

# Testing

[Greenmail standalone](https://greenmail-mail-test.github.io/greenmail/#deploy_docker_standalone) IMAP server is configured in the docker compose file for testing. To start it, run the following command:

```shell
docker compose up
```

Once the IMAP server is up and running, run the following command to execute tests:

```shell
composer test
```