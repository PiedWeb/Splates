+++
title = "Nesting"
linkTitle = "Templates Nesting"
[menu.main]
parent = "templates"
weight = 4
+++

Including another template into the current template is done using the `fetch()` and `echo` functions:

```php
<?=$this->fetch('partials/header') ?>

<p>Your content.</p>

<?=$this->fetch('partials/footer') ?>
```

The `fetch()` function also works with [folders]({{< relref "engine/folders.md" >}}):

```php
<?=$this->fetch('partials::header') ?>
```

## Assign data

To assign data (variables) to a nested template, pass them as an array to the `fetch()` functions. This data will then be available as locally scoped variables within the nested template.

```php
<?=$this->fetch('partials/header', ['name' => 'Jonathan']) ?>

<p>Your content.</p>

<?=$this->fetch('partials/footer') ?>
```
