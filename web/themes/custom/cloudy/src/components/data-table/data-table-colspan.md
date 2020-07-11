## Span multiple columns

- Use `colspan` to specify the number of columns a cell should span.

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
        <th scope="col" colspan="2">Heading</th>
      </tr>
      <tr></tr>
    </thead>

    <tbody>
      <tr>
        <td>data</td>
        <td>data</td>
        <td>data</td>
        <td>data</td>
      </tr>
    </tbody>
  </table>
</div>
```
