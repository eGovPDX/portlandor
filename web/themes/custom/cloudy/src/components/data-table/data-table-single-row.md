## Table with single row

- Use when there is a single row of data
- Also consider using **table with header cells in the first column only** which may be a better alternative depending on how you wish to present your tabular data

### Usage

```html
<div class="table-responsive">
  <table class="table table-bordered table-hover">
    <caption>
      Caption text goes here.
    </caption>
    <thead class="thead-dark">
      <tr>
        <th scope="col">Heading</th>
        <th scope="col">Heading</th>
        <th scope="col">Heading</th>
      </tr>
      <tr></tr>
    </thead>
    <tbody>
      <tr>
        <th>data</th>
        <td>data</td>
        <td>data</td>
      </tr>
    </tbody>
  </table>
</div>
```
