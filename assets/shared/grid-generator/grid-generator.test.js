import { createGridArea, createGrid } from "./grid-generator";

test("Create grid area: from a to d", () => {
  expect(createGridArea(["a", "d"])).toBe("a / a / d / d");
});

test("Create grid area: from b to k", () => {
  expect(createGridArea(["b", "k"])).toBe("b / b / k / k");
});

test("Create grid area: from b to k with multiple array values", () => {
  expect(createGridArea(["b", "k", "dd", "k"])).toBe("b / b / k / k");
});

test("Create grid: 2x2", () => {
  expect(createGrid(2, 2)).toBe("'a b'\n 'c d'\n ");
});

test("Create grid: 10x2", () => {
  expect(createGrid(10, 2)).toBe(
    "'a b'\n 'c d'\n 'e f'\n 'g h'\n 'i j'\n 'k l'\n 'm n'\n 'o p'\n 'q r'\n 's t'\n "
  );
});

test("it works with large grids", () => {
  expect(createGrid(44, 4)).toBe(
    "'a b c d'\n 'e f g h'\n 'i j k l'\n 'm n o p'\n 'q r s t'\n 'u v w x'\n 'y z aa bb'\n 'cc dd ee ff'\n 'gg hh ii jj'\n 'kk ll mm nn'\n 'oo pp qq rr'\n 'ss tt uu vv'\n 'ww xx yy zz'\n 'aaa bbb ccc ddd'\n 'eee fff ggg hhh'\n 'iii jjj kkk lll'\n 'mmm nnn ooo ppp'\n 'qqq rrr sss ttt'\n 'uuu vvv www xxx'\n 'yyy zzz aaaa bbbb'\n 'cccc dddd eeee ffff'\n 'gggg hhhh iiii jjjj'\n 'kkkk llll mmmm nnnn'\n 'oooo pppp qqqq rrrr'\n 'ssss tttt uuuu vvvv'\n 'wwww xxxx yyyy zzzz'\n 'aaaaa bbbbb ccccc ddddd'\n 'eeeee fffff ggggg hhhhh'\n 'iiiii jjjjj kkkkk lllll'\n 'mmmmm nnnnn ooooo ppppp'\n 'qqqqq rrrrr sssss ttttt'\n 'uuuuu vvvvv wwwww xxxxx'\n 'yyyyy zzzzz aaaaaa bbbbbb'\n 'cccccc dddddd eeeeee ffffff'\n 'gggggg hhhhhh iiiiii jjjjjj'\n 'kkkkkk llllll mmmmmm nnnnnn'\n 'oooooo pppppp qqqqqq rrrrrr'\n 'ssssss tttttt uuuuuu vvvvvv'\n 'wwwwww xxxxxx yyyyyy zzzzzz'\n 'aaaaaaa bbbbbbb ccccccc ddddddd'\n 'eeeeeee fffffff ggggggg hhhhhhh'\n 'iiiiiii jjjjjjj kkkkkkk lllllll'\n 'mmmmmmm nnnnnnn ooooooo ppppppp'\n 'qqqqqqq rrrrrrr sssssss ttttttt'\n "
  );
});

test("Create grid area: different values", () => {
  expect(createGridArea(["a", "p", "c", "p"])).toBe("a / a / p / p");
  expect(createGridArea(["q", "aaa"])).toBe("q / q / aaa / aaa");
  expect(createGridArea(["eee", "oooo"])).toBe("eee / eee / oooo / oooo");
  expect(createGridArea(["ssss", "ssssss"])).toBe(
    "ssss / ssss / ssssss / ssssss"
  );
  expect(createGridArea(["wwwwww", "ttttttt"])).toBe(
    "wwwwww / wwwwww / ttttttt / ttttttt"
  );
  expect(createGridArea(["r", "vvvvvv"])).toBe("r / r / vvvvvv / vvvvvv");
});
