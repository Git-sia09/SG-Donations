# SG Donations — XenForo Addon

A donation tracking addon with a progress bar widget for XenForo 2.3.10+.

## Features

- Configurable donation goal, currency and title via Admin Options
- Database-backed donation log that tracks individual donations
- Progress bar widget using the XenForo 2.3 Widget system
- Admin panel for managing donations and viewing logs
- Public-facing donation form with recent donors list

## Requirements

- **XenForo** 2.3.10 or newer (`version_id` ≥ 2031000)
- **PHP** 8.0 or newer

## Installation

1. Upload the contents of `src/addons/SG/Donations/` to `src/addons/SG/Donations/` on your XenForo server.
2. Log in to your Admin Control Panel.
3. Navigate to **Admin → Add-ons**.
4. Click **Install / Upgrade from File** and select (or confirm) `SG/Donations`.
5. Follow the on-screen steps to complete installation.

## Configuration

After installation, configure the addon via:

**Admin → Options → SG Donations Settings**

| Option | Default | Description |
|--------|---------|-------------|
| Donation Goal Amount | `1000` | The monetary target you wish to reach |
| Donation Currency | `USD` | ISO 4217 currency code shown alongside amounts |
| Donation Widget Title | `Donation Goal` | Heading displayed inside the progress bar widget |

## Widget Setup

1. Go to **Appearance → Widgets**.
2. Click **Add Widget**.
3. Select **Donation Progress** from the widget definition list.
4. Choose a widget position, configure the title, and save.

The widget will display the current progress bar on any page that renders the chosen widget position.

## Admin Panel

Manage individual donations (toggle visibility, delete) at:

```
/admin.php?donations/
```

## Public Donation Page

Visitors can submit donations and view recent donors at:

```
/donations/
```

## Uninstallation

Go to **Admin → Add-ons**, find **SG Donations** and click **Uninstall**. This will remove the `xf_sg_donation` table and all associated data.
