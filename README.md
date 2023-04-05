# OVH GAME Firewall Disabler
#### **Programmatically disable OVH's GAME firewall in all GAME servers**

## Table of Contents

- [**Why?**](#why)
- [**Requirements**](#requirements)
- [**Installation**](#installation)
- [**Executing**](#executing)

## Why?

Well, the reason why I made this in the first place is that the account I
was managing had **quite the amount** of GAME dedicated servers, some even
had failover IP addresses and subnets allocated.

In this specific scenario, we wanted to disable the firewall because we
did not see a need for it. The applications we're running are not bound
to specific ports or port ranges.

The GAME firewall can be configured for each address individually, so I
made this very simple PHP script using OVH's official PHP API wrapper to
go through every IP address in every subnet in every active GAME server
in the account, and disable the "GAME restriction" (in other words, their
GAME firewall).

## Requirements

- Any OS that can run PHP (yes, even Windows)
- PHP 8.1+
- Composer
- Git (optional)

## Installation

**Note!** The instructions assume you're running this on a Linux box
of some kind.

### 1. Clone the repository

```bash
git clone https://github.com/BetTD/ovh-game-firewall-disabler
```

*(alternatively, you can download the repository to a ZIP file and
extract that)*

### 2. Copy the `secrets.example.php` to `secrets.php`

```bash
cp secrets.example.php secrets.php
```

### 3. Add your API credentials to the `secrets.php` file

Open the file with your editor of choice and fill in the credentials.
The file has comments explaining how to obtain them.

### 4. Install the dependencies using Composer

```bash
composer install --no-dev
```

## Executing

Simply run the `src/disable-firewall.php` file with PHP!

```bash
php src/disable-firewall.php
```
