#!/usr/bin/env python3
"""
Independent oracle for the oligo-skew expected values of Tests/Service/SkewsManagerTest.php.

It does not call any PHP: it recomputes, from the definition, the distance between the
oligonucleotide counts of the whole sequence and of each window.

  * two strands: the whole sequence and each window are counted on  seq + " " + revcomp(seq)
    (the space stops a word from running across the junction);
  * one strand: the sequence and each window are counted as they are;
  * "pearson": 1 - r, the plain Pearson correlation of the counts of all the 4^k words;
  * "almeida": 1 - r_w, the correlation weighted by x * y (Almeida et al, 2001), rounded to 8 decimals.

Usage (from the repository root):

  python3 -I Tests/Oracle/oligo_skew_oracle.py pearson 2 100 2   # two-strand test, k=2, window 100
  python3 -I Tests/Oracle/oligo_skew_oracle.py almeida 2 100 1   # asymmetric 600 bp sequence, one strand
  python3 -I Tests/Oracle/oligo_skew_oracle.py almeida 2 100 2

Arguments: distance k window strands [test-name]. The sequence is read from the test file: by default
testOligoSkewArrayCalculationTwoStrands for "pearson" and testOligoSkewWithTheAlmeidaDistance for
"almeida". The period is max(10, ceil(length / 1400)), like the service.
"""
import itertools
import math
import re
import sys

TEST_FILE = "Tests/Service/SkewsManagerTest.php"
DEFAULT_TEST = {
    "pearson": "testOligoSkewArrayCalculationTwoStrands",
    "almeida": "testOligoSkewWithTheAlmeidaDistance",
}
COMPLEMENT = {"A": "T", "T": "A", "C": "G", "G": "C"}


def read_sequence(test_name):
    source = open(TEST_FILE, encoding="utf-8").read()
    start = source.index("function " + test_name)
    return re.search(r'\$s?[sS]equence = "([ACGT]+)"', source[start:]).group(1)


def reverse_complement(sequence):
    return "".join(COMPLEMENT[base] for base in reversed(sequence))


def counts(text, k):
    words = ["".join(p) for p in itertools.product("ACGT", repeat=k)]
    table = {word: 0 for word in words}
    for i in range(len(text) - k + 1):
        word = text[i:i + k]
        if word in table:
            table[word] += 1
    return words, table


def pearson(words, x, y):
    a = [x[w] for w in words]
    b = [y[w] for w in words]
    ma, mb = sum(a) / len(a), sum(b) / len(b)
    num = sum((u - ma) * (v - mb) for u, v in zip(a, b))
    den = math.sqrt(sum((u - ma) ** 2 for u in a) * sum((v - mb) ** 2 for v in b))
    return 1 - num / den


def almeida(words, x, y):
    a = [x[w] for w in words]
    b = [y[w] for w in words]
    weights = [u * v for u, v in zip(a, b)]
    total = sum(weights)
    xm = sum(w * u for w, u in zip(weights, a)) / total
    ym = sum(w * v for w, v in zip(weights, b)) / total
    sx = sum(w * (u - xm) ** 2 for w, u in zip(weights, a)) / total
    sy = sum(w * (v - ym) ** 2 for w, v in zip(weights, b)) / total
    r = sum(w * (u - xm) * (v - ym) for w, u, v in zip(weights, a, b)) / (total * math.sqrt(sx * sy))
    return round(1 - r, 8)


def main():
    kind, k, window, strands = sys.argv[1], int(sys.argv[2]), int(sys.argv[3]), int(sys.argv[4])
    test_name = sys.argv[5] if len(sys.argv) > 5 else DEFAULT_TEST[kind]
    distance = pearson if kind == "pearson" else almeida
    sequence = read_sequence(test_name)
    period = max(10, math.ceil(len(sequence) / 1400))

    def view(text):
        return text + " " + reverse_complement(text) if strands == 2 else text

    words, reference = counts(view(sequence), k)
    for start in range(0, len(sequence) - window + 1, period):
        _, current = counts(view(sequence[start:start + window]), k)
        print("%d => %r," % (start, distance(words, reference, current)))


if __name__ == "__main__":
    main()
