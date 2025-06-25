# Grid-generator

This is a grid generator for os2display.

## createGrid

Input is columns and rows, from which it creates and returns a string for
[grid area templates](https://developer.mozilla.org/en-US/docs/Web/CSS/grid-template-areas) css property.

```javascript
createGrid(2, 2);
```

Return:

```text
'a b'
'c d'
```

and

```javascript
createGrid(3, 4);
```

Return:

```text
'a b c d'
'e f g h'
'i j k l'
```

## createGridArea

Input is an array defining which columns the grid area should span.

output is a string for the [grid-area](https://developer.mozilla.org/en-US/docs/Web/CSS/grid-area) css property.

```javascript
createGridArea(["a", "d"]);
```

Return:

```text
"a / a / d / d"
```

## Testing

With jest

```bash
yarn test
```

## Linting

```bash
check-coding-standards
apply-coding-standards
```
