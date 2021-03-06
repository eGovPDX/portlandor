## Dense table

- Use when you need a more compact table containing a lot of data.
- Include the `table-sm` to create a dense style table.
- Copy the table template code below and update to match your data.

### Usage

```html
<div class="table-responsive">
  <table class="table table-bordered table-hover table-sm">
    <caption>
      Comparison between different types of coffee drinks.
    </caption>
    <thead class="thead-dark">
      <tr>
        <th scope="col">Coffee Types</th>
        <th scope="col">Milk</th>
        <th scope="col">Espresso Shots</th>
        <th scope="col">Milk Foam</th>
        <th scope="col">Water</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <th scope="row">Cappuccino</th>
        <td>Yes</td>
        <td>2</td>
        <td>Yes</td>
        <td>No</td>
      </tr>
      <tr>
        <th scope="row">Macchiato</th>
        <td>No</td>
        <td>1</td>
        <td>Yes</td>
        <td>No</td>
      </tr>
      <tr>
        <th scope="row">Flat White</th>
        <td>Yes</td>
        <td>1</td>
        <td>No</td>
        <td>No</td>
      </tr>
      <tr>
        <th scope="row">Latte</th>
        <td>Yes</td>
        <td>1</td>
        <td>Yes</td>
        <td>No</td>
      </tr>
    </tbody>
  </table>
</div>
```
