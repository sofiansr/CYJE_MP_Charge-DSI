# CYJE_MP_Charge-DSI - Espace Gestion de prospects

## Manuel Utilisateur

Le présent document est un **manuel utilisateur** du site "Espace Gestion de Prospects" de la Mission Piou de NISAR Sofiane, pour le poste de Chargé DSI à la CY Junior Engineering.

## Introduction

Conformément à l'énoncé de la Mission Piou, ce site est un **outil web de suivi des prospects destiné à améliorer la gestion commerciale de CY Junior Engineering**.</br>
Elle **centralise** les informations de prospection, permet de **suivre le statut de chaque prospect** et de faciliter la communication entre les membres du pôle Développement et le reste de la Junior.</br>
Chaque utilisateur peut **consulter**, **ajouter, modifier, ou supprimer** des prospects.</br>


-------
### Connexion

![](https://i.imgur.com/WoxrUSe.png)

- Rendez-vous sur la **page de connexion**.
- Rentrez vos identifiants. Ils vous ont été fournis par votre Chargé DSI.
- Cliquez sur ```Se connecter```. Vous serez alors redirigé vers la page d'accueil.

> [!TIP]
> Contactez votre Chargé DSI si vous n'avez pas d'identifiants de connexion.

> [!CAUTION]
> **Vos identifiants sont strictement personnels et ne doivent pas être partagés.**

-------
### Page d'accueil

Bienvenue sur la page d'accueil.

Vous pouvez à tout moment naviguer entre les différentes pages du site, ou vous déconnecter, via le menu supérieur.</br>
Les données (chiffres, graphiques, statistiques...) sont directement issues de la base de données des prospects.</br>
Elles permettent une vue rapide sur l'état actuel de l'efficacité de la prospection globale de la CY Junior Engineering.</br>
Voici une explication pour chaque case :
| Nom de la case | Explication |
| :-------- | :------- |
| Prospects totaux | Simple compteur du nombre de prospects enregistré dans la base de données |
| Prospects contactés ce mois | Compteur du nombre de prospects contacté pour le mois actuel (du 1er jusqu'à la fin du mois actuel) |
| Prospects contacté / utilisateur en moyenne | (Nombre d'utilisateurs / nombre de prospects avec le statut ```Contacté``` |
| Type 1er contact | Graphique en barre récapitulatif de tous les types de premiers contacts des prospects de la base de données |
| Répartition chaleur | Graphique en camembert récapitulatif de toutes les chaleurs des prospects de la base de données |
| Offre prestation | Graphique en camembert récapitulatif de toutes les offres de prestations proposées aux prospects de la base de données |
| Taux de conversion | (Nombre de prospects / Nombre de prospects avec le statut ```Signé``` |
| statuts prospects | Graphique en barre récapitulatif de tous les statuts des prospects de la base de données |
| 5 derniers prospects contactés | Liste des 5 derniers prospects contactés (Colonne ```Relancé le``` du tableau de la page de recherche de prospects) |

> [!NOTE]
> Chaque modification de la base de données des prospects ou utilisateurs est susceptible de modifier le contenu d'une ou plusieurs de ces cases.

-------
### Page de recherche de prospects

![](https://i.imgur.com/PN6OmfX.png)
Bienvenue sur la page de recherche de prospects.

Par défaut, vous verrez une liste de 20 prospects. Vous pouvez naviguer à travers les pages, en cliquant sur les boutons ```Précédent``` et ```Suivant```, en bas du tableau.</br>
Un **prospect** (une ligne) peut être composé de **plusieurs contacts** (voir colonne ```Nom(s)```, ```Prénom(s)```, ```Email(s)```, ```Tel(s)```, ```Poste(s)```).</br>
Cette liste est composée de 17 colonnes :
| Nom de la colonne | Explication |
| :-------- | :------- |
| ID | Utilisé uniquement par l'administrateur. Vous pouvez ignorer cette colonne. |
| Entreprise | Nom de l'entreprise prospectée. |
| Secteur | Secteur de l'entreprise prospectée. |
| statuts | Statut du prospect.</br>Les statuts possibles sont :</br> ```A contacter```, ```Contacté```, ```A rappeler```, ```Relancé```, ```RDV```, ```PC```, ```Signé```, ```PC refusée```, ```Perdu``` |
| Nom(s) | Noms du ou des contacts de ce prospect. |
| Prénom(s) | Prénoms du ou des contacts de ce prospect. |
| Email(s) | Emails du ou des contacts de ce prospect. |
| Tel(s) | Numéros de téléphones du ou des contacts de ce prospect. |
| Poste(s) | Postes du ou des contacts de ce prospect dans leur entreprise. |
| Relancé le | Date de la dernière relance de ce prospect, s'il a été relancé. |
| Type acquisition | Type d'acquisition de ce prospect.</br> Les types possibles sont :</br>```DE```, ```Appel d'offre```, ```Web crawling```, ```Porte à porte```, ```IRL```, ```Fidélisation```, ```BaNCO```, ```Partenariat``` |
| Date 1er contact | Date du premier contact de ce prospect. |
| Type 1er contact | Type du premier contact de ce prospect</br>Les types possibles sont :</br>```Porte à porte```, ```Formulaire de contact```, ```Event CY Entreprise```, ```LinkedIn```, ```Mail```, ```Appel d```offre```, ```DE```, ```Cold call```, ```Salon``` |
| Chaleur | Chaleur ressentie par les prospecteurs par rapport à ce prospect.</br>Les chaleurs possibles sont</br>```Froid```, ```Tiède```, ```Chaud``` |
| Offre prestation | Offre de prestation proposée à ce prospect.</br>Les offres de prestations possibles sont</br>```Informatique```, ```Chimie```, ```Biotechnologies```, ```Génie civil``` |
| Chef de projet | Nom et prénom du chef de projet rattaché à ce prospect. |
| Détails | Voir ci-dessous. |

Le bouton ```Détails...``` de chaque ligne du tableau (chaque prospect) affichera toutes les données du prospect (voir colonnes ci-dessus), **ainsi que les champs complémentaires suivants** :
| Nom du champ complémentaire | Explication |
| :-------- | :------- |
| Adresse | Adresse physique de l'entreprise prospectée |
| Site web | Site web de l'entreprise prospectée |
| Commentaire | Zone de commentaire libre, partagée, et modifiable par chaque utilisateur |

Également, vous avez la possibilité de **modifier** les données du prospect affiché en cliquant sur le bouton ```Modifier```, puis ```Enregistrer``` ou ```Annuler```.</br>
Le bouton ```Modifier``` débloquera les champs, vous permettant de les modifier. N'oubliez pas d'enregistrer ou d'annuler vos modifications.</br>
Vous pouvez aussi **supprimer** le prospect en cliquant sur le bouton ```Supprimer```.

> [!CAUTION]
> La suppression ou la modification d'un prospect et/ou de ses contacts **est définitive**.

![](https://i.imgur.com/2Hs2bGP.png)
![](https://i.imgur.com/pV7CVml.png)

Vous pouvez **ajouter** un prospect et remplir les données associées à ce prospect en cliquant sur le bouton ```Ajouter un prospect```.
Cela affichera une interface (similaire à la modification de prospect), vous permettant d'écrire les données.
![](https://i.imgur.com/5qfTsLr.png)

Vous avez la possibilité de **rechercher** un prospect via la barre de recherche, ainsi que de **filtrer** les résultats obtenus via la liste ```Filtrer par``` juste à droite de la barre de recherche. Un champ à droite de cette liste apparaîtra pour vous permettre de filtrer selon ce que vous avez sélectionné dans cette liste.</br>
Cliquez sur ```Réinitialiser``` pour désactiver le filtrage ou la recherche.</br>
Vous pouvez également **trier** les résultats du tableau en cliquant sur les colonnes ```Entreprise```, ```Secteur```, ```Relancé le```, ```Date 1er contact```, ```Chef de projet```, de façon alphabétique ou chronologique.</br>
![](https://i.imgur.com/B7zPfwq.png)


-------
### Page d'administration (ADMIN uniquement)

![](https://i.imgur.com/O3Q7jwB.png)
Bienvenue sur la page d'administration des utilisateurs.

Cette page est réservée aux administrateurs uniquement. Tout utilisateur non administrateur se verra bloquer son accès à cette page pour des raisons de sécurité.

Ici, vous pouvez consulter la liste de tous les utilisateurs (utilisateurs et administrateurs) de la base de données.</br>
Vous pouvez également modifier ou supprimer un utilisateur, en choississant l'utilisateur dans la liste, puis en cliquant sur le bouton ```Modifier```.</br>
Le bouton ```Modifier``` débloquera les champs, vous permettant de les modifier. N'oubliez pas d'enregistrer ou d'annuler vos modifications.</br>

Vous pouvez ajouter un utilisateur en cliquant sur le bouton ```Ajouter```, en remplissant les données de l'utilisateur, et en cliquant ensuite sur le bouton ```Enregistrer```.</br>
Un mot de passe sera affiché à l'écran, veuillez le transmettre à l'utilisateur en question.

> [!CAUTION]
> La suppression ou la modification d'un utilisateur **est définitive**.</br>
> Veillez à avoir au minimum un administrateur actif dans la base de données pour éviter tout blocage. 

-------
