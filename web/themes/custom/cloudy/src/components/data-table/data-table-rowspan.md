## Span multiple rows

- Use `rowspan` to specify the number of rows a cell should span.

## Usage

```html
<div class="table-responsive">
  <table class="table table-bordered table-hover">
    <caption>
      Caption here.
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
        <th scope="row" rowspan="2">data</th>
        <td>data</td>
        <td>data</td>
      </tr>
      <tr>
        <td>data</td>
        <td>data</td>
      </tr>
    </tbody>
  </table>
</div>
```
