# Instrukcja obsługi i konfiguracji

Pasek postępu ma trzy ustawienia, wszystkie w jednym miejscu. To, które z nich mają znaczenie,
zależy od tego, gdzie sklep trzyma próg darmowej dostawy.

## Konfiguracja

Przejdź do `Stores > Configuration > EcomHouse > Free Shipping Goal Bar`:

| Pole | Wartość |
|---|---|
| **Enable Goal Bar** | `Yes` |
| **Goal Amount Taken From** | `Free Shipping shipping method` albo `Manual amount` — patrz niżej |
| **Goal Amount** | próg w walucie bazowej (tylko gdy pole wyżej to `Manual amount`) |

Kliknij **Save Config**, a następnie wyczyść cache:

```bash
bin/magento cache:flush
```

Wszystkie ustawienia mają zakres widoku sklepu, więc każdy store view może mieć własny próg.

### Tryb 1 — odwzorowanie metody dostawy Free Shipping (zalecany)

Ustaw **Goal Amount Taken From** na `Free Shipping shipping method`. Pasek czyta wtedy próg z
`Stores > Configuration > Sales > Delivery Methods > Free Shipping > Minimum Order Amount` i
porównuje koszyk dokładnie tak, jak robi to ta metoda — więc to, co pasek obiecuje, checkout
faktycznie realizuje.

Gdy metoda Free Shipping jest **wyłączona**, pasek się nie pokazuje: nie ma wtedy progu, do
którego można iść, a moduł żadnego nie wymyśla.

### Tryb 2 — próg wpisany ręcznie

Ustaw **Goal Amount Taken From** na `Manual amount` i wypełnij **Goal Amount** (np. `150`).

Ten tryb jest dla sklepów, w których darmowa dostawa wynika z reguły cenowej koszyka, a nie z
natywnej metody dostawy. **Pasek nie tworzy darmowej dostawy** — tylko ją reklamuje. Upewnij się,
że reguła przyznająca darmową dostawę działa od tej samej kwoty, inaczej klient zobaczy obietnicę,
której checkout nie dotrzyma.

Przy wartości `0` pasek pozostaje ukryty.

## Co widzi klient

- **Poniżej progu** — „Dodaj jeszcze *X*, aby otrzymać darmową dostawę!" oraz pasek od `0` do
  progu.
- **Na progu lub powyżej** — „Gratulacje! Twoja przesyłka jest bezpłatna!"

Oba stany pojawiają się na stronie koszyka (nad zawartością koszyka) i w minikoszyku, pod
podsumowaniem. Pusty koszyk oraz koszyk wirtualny (same produkty do pobrania — nie ma czego
wysyłać) nie pokazują niczego.

## Jaka kwota jest porównywana z progiem

**Wartość koszyka po rabatach**, w walucie bazowej sklepu — dokładnie ta, którą porównuje natywna
metoda Free Shipping. Konkretnie:

- ceny katalogowe wpisywane **bez** podatku → wartość netto + rabaty;
- ceny katalogowe wpisywane **z** podatkiem → wartość brutto + rabaty;
- włączona opcja **Tax Including** samej metody Free Shipping → wartość po rabatach plus podatek.

Koszt dostawy, pakowanie na prezent i punkty lojalnościowe **nie** wchodzą do tej kwoty — celowo,
bo przewoźnik również ich nie bierze pod uwagę.

W sklepie wielowalutowym porównanie odbywa się w walucie bazowej, a przeliczane są wyłącznie
kwoty wyświetlane, więc pasek pokazuje to samo niezależnie od waluty przeglądania.

## Tłumaczenia

Moduł zawiera `en_US` i `pl_PL`. Aby zmienić treści, dodaj własne tłumaczenie tych fraz
źródłowych w motywie lub w paczce językowej:

```
"Add %1 more to your cart and get free shipping!","<Twoja treść z %1>"
"Congratulations! Your shipment is free!","<Twoja treść>"
```

`%1` zostaje zastąpione sformatowaną brakującą kwotą, otoczoną znacznikiem
`<span class="freeshipping-goal-bar-amount">`, który można stylować osobno.

## Stylowanie

Moduł dostarcza wstępne style w `view/frontend/web/css/source/_module.less`. Każdy kolor jest
zmienną LESS, więc motyw może przestylować pasek bez kopiowania pliku:

```less
// w pliku motywu web/css/source/_extend.less
@freeshipping-goal-bar-background: #fff8e1;
@freeshipping-goal-bar-accent-color: #d35400;
@freeshipping-goal-bar-reached-background: #e5efe5;
```

| Klasa | Element |
|---|---|
| `.freeshipping-goal-bar` | kontener zewnętrzny (oba miejsca) |
| `.freeshipping-goal-bar-reached` | dokładana do kontenera po osiągnięciu progu |
| `.freeshipping-goal-bar-message` | zdanie nad paskiem |
| `.freeshipping-goal-bar-amount` | wyróżniona brakująca kwota |
| `.freeshipping-goal-bar-track` | tło paska |
| `.freeshipping-goal-bar-fill` | wypełniona część paska |
| `.freeshipping-goal-bar-limit` | etykiety `0` i progu po bokach paska |

Moduł nigdzie nie używa `!important`, więc zwykłe reguły motywu wygrywają.

## Obsługiwane frontendy

Moduł jest przeznaczony dla **Lumy**. Komponent minikoszyka to komponent Knockout, którego Breeze
nie uruchamia — dla tego frontendu zainstaluj nakładkę `ecomhouse/breezefreeshippinggoalbar`.
Sekcja customer-data to zwykły JSON, więc nakładka konsumuje ją wprost, bez powielania obliczeń.

## Rozszerzanie: własne źródło progu

Próg pochodzi z implementacji `EcomHouse\FreeShippingGoalBar\Api\GoalProviderInterface`,
wybieranej ustawieniem w adminie. Własną rejestrujesz w module projektowym:

```xml
<type name="EcomHouse\FreeShippingGoalBar\Model\Goal\GoalResolver">
    <arguments>
        <argument name="providers" xsi:type="array">
            <item name="customer_group" xsi:type="object">Vendor\Module\Model\GroupGoalProvider</item>
        </argument>
    </arguments>
</type>
```

Gdy dla danego koszyka nie ma progu, zwróć `null` z `getThreshold()` — pasek się wtedy ukryje,
zamiast pokazywać bezsensowne zero.

## Rozwiązywanie problemów

### Pasek nie pojawia się nigdzie

1. **Enable Goal Bar** musi być `Yes` dla oglądanego widoku sklepu.
2. Sprawdź, czy próg faktycznie się wylicza:
   - tryb metody dostawy → `Delivery Methods > Free Shipping` musi być **włączone** i mieć
     niezerową kwotę minimalną;
   - tryb ręczny → **Goal Amount** musi być większe od `0`.
3. Koszyk musi zawierać coś do wysyłki — pusty i wirtualny nie pokazują nic.
4. `bin/magento module:status EcomHouse_FreeShippingGoalBar` powinno zwrócić `Module is enabled`.
5. Wyczyść cache i przebuduj pliki statyczne:

   ```bash
   bin/magento cache:flush
   bin/magento setup:static-content:deploy -f
   ```

### Pasek jest na stronie koszyka, ale nie ma go w minikoszyku

Minikoszyk zasila sekcja customer-data `ecomhouse-freeshipping-goal-bar`, którą przeglądarka
trzyma w `localStorage`. Po zmianie konfiguracji zapisana kopia może być jeszcze stara.

- Dodaj lub usuń dowolną pozycję koszyka — to unieważnia sekcję i pasek się dociąga.
- Albo wyczyść dane witryny w przeglądarce.

Dotyczy wyłącznie osoby ze starą kopią w przeglądarce; nowi odwiedzający od razu dostają aktualne
wartości.

### Pasek mówi, że dostawa jest darmowa, a checkout ją nalicza

Jesteś w trybie ręcznym, a w Magento nic nie przyznaje darmowej dostawy przy tej kwocie. Przełącz
się na tryb metody dostawy albo utwórz regułę cenową koszyka z akcją **Free Shipping** i
odpowiadającym warunkiem wartości koszyka.

### Pasek stoi w innym miejscu niż w starym module

Tak ma być. `ecomhouse/freeshippingprogressbar` porównywał wartość całego zamówienia pomniejszoną
o dostawę; ten moduł porównuje wartość koszyka po rabatach, tak jak robi to przewoźnik. W sklepie
z cenami brutto pasek zwykle się przesuwa.

## Najczęstsze pytania

**Czy działa dla gości?**
Tak. Wszystko wynika z koszyka (quote), który istnieje również dla niezalogowanych.

**Czy spowalnia koszyk?**
Nie ma dodatkowych zapytań: obliczenie korzysta z załadowanego już koszyka i z cache'owanej
konfiguracji, a wartości do minikoszyka jadą razem z żądaniem customer-data, które Magento i tak
wykonuje.

**Czy mogę pokazać pasek gdzie indziej, np. na karcie produktu?**
Tak. Pasek to komponent Knockout czytający sekcję customer-data
`ecomhouse-freeshipping-goal-bar` — zainicjuj `EcomHouse_FreeShippingGoalBar/js/view/goal-bar` we
własnym szablonie albo odczytaj tę sekcję z własnego JS.

**Czy moduł zmienia sposób naliczania dostawy?**
Nie. Nigdy nie wpina się w dostawę, sumy ani przewoźników. Odczytuje i wyświetla.

**Czy moduł przechowuje dane klienta?**
Nie. Nie zapisuje niczego w bazie ani w sesji; sekcja customer-data jest wyliczana z koszyka przy
każdym żądaniu.
