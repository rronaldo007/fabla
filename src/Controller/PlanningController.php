<?php

namespace App\Controller;

use App\Form\ReservationEditType;
use App\Form\ReservationExchangeType;
use App\Entity\SharedResource;
use App\Entity\Reservation;
use App\Form\ReservationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

final class PlanningController extends AbstractController
{
    #[Route('/planning', name: 'app_planning')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $resources = $entityManager->getRepository(SharedResource::class)->findAll();
        $reservations = $entityManager->getRepository(Reservation::class)->findAll();
        
        $calendarEvents = [];
        $formattedReservations = [];
        $currentUser = $this->getUser();
        $userReservationIds = [];
        
        foreach ($reservations as $reservation) {
            $startTime = $reservation->getStartTime();
            $endTime = $reservation->getEndTime();
            
            if ($startTime instanceof \DateTime && $endTime instanceof \DateTime) {
                // Format calendar events
                $calendarEvents[] = [
                    'title' => sprintf(
                        '%s - %s %s (%s)', 
                        $reservation->getResource()->getName(),
                        $reservation->getReservedBy()->getUserProfile()->getFirstName(),
                        $reservation->getReservedBy()->getUserProfile()->getLastName(),
                        $startTime->format('H:i') == '08:00' ? 'AM' : 'PM'
                    ),
                    'start' => $startTime->format('Y-m-d\TH:i:s'),
                    'end' => $endTime->format('Y-m-d\TH:i:s'),
                    'backgroundColor' => $startTime->format('H:i') == '08:00' ? '#007bff' : '#17a2b8',
                    'borderColor' => $startTime->format('H:i') == '08:00' ? '#0056b3' : '#138496'
                ];
                
                // Format table data
                $formattedReservations[] = [
                    'id' => $reservation->getId(),
                    'resource' => [
                        'name' => $reservation->getResource()->getName()
                    ],
                    'startTime' => $startTime->format('Y-m-d H:i'),
                    'endTime' => $endTime->format('Y-m-d H:i'),
                    'reservedBy' => [
                        'firstName' => $reservation->getReservedBy()->getUserProfile()->getFirstName(),
                        'lastName' => $reservation->getReservedBy()->getUserProfile()->getLastName()
                    ]
                ];
                
                // Track user's reservations
                if ($currentUser && $reservation->getReservedBy() === $currentUser) {
                    $userReservationIds[] = $reservation->getId();
                }
            }
        }
        
        return $this->render('planning/index.html.twig', [
            'resources' => $resources,
            'reservations' => $formattedReservations,
            'calendarEvents' => json_encode($calendarEvents),
            'user_reservations' => $userReservationIds
        ]);
    }


    #[Route('/reservation/new/{id}', name: 'app_reservation_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SharedResource $resource
    ): Response {
        $reservation = new Reservation();
        $reservation->setResource($resource);
        
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Time slots are already validated by the form's choice type
            // and converted to proper DateTime objects by the form event listener
            
            // Check for overlapping reservations
            $existingReservation = $entityManager->getRepository(Reservation::class)
                ->findOverlappingReservation(
                    $reservation->getResource(),
                    $reservation->getStartTime(),
                    $reservation->getEndTime()
                );
            
            if ($existingReservation) {
                $this->addFlash('error', 'This time slot is already reserved.');
                return $this->redirectToRoute('app_reservation_new', ['id' => $resource->getId()]);
            }
            
            $reservation->setReservedBy($this->getUser());
            
            $entityManager->persist($reservation);
            $entityManager->flush();
            
            $this->addFlash('success', 'Reservation created successfully.');
            return $this->redirectToRoute('app_planning');
        }
        
        return $this->render('planning/create.html.twig', [
            'form' => $form->createView(),
            'resource' => $resource->getName()
        ]);
    }

    #[Route('/reservation/edit/{id}', name: 'app_reservation_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        Reservation $reservation
    ): Response {

        $form = $this->createForm(ReservationEditType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check for overlapping reservations
            $existingReservation = $entityManager->getRepository(Reservation::class)
                ->findOverlappingReservation(
                    $reservation->getResource(),
                    $reservation->getStartTime(),
                    $reservation->getEndTime()
                );

            // Exclude current reservation from overlap check
            if ($existingReservation && $existingReservation->getId() !== $reservation->getId()) {
                $this->addFlash('error', 'This time slot is already reserved.');
                return $this->redirectToRoute('app_reservation_edit', ['id' => $reservation->getId()]);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Reservation updated successfully.');
            return $this->redirectToRoute('app_planning');
        }

        return $this->render('planning/edit.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation
        ]);
    }

    #[Route('/reservation/exchange/{id}', name: 'app_reservation_exchange')]
    public function exchange(
        Request $request,
        EntityManagerInterface $entityManager,
        Reservation $reservation
    ): Response {

        $form = $this->createForm(ReservationExchangeType::class, null, [
            'excludeUser' => $this->getUser()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $targetUser = $form->get('user')->getData();
            
            // Find target user's reservation on the same day if any
            $targetReservation = $entityManager->getRepository(Reservation::class)
                ->findOneBy([
                    'reservedBy' => $targetUser,
                    'startTime' => $reservation->getStartTime()
                ]);

            if ($targetReservation) {
                // Swap reservations
                $tempUser = $reservation->getReservedBy();
                $reservation->setReservedBy($targetReservation->getReservedBy());
                $targetReservation->setReservedBy($tempUser);
            } else {
                // Simply transfer the reservation
                $reservation->setReservedBy($targetUser);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Reservation exchanged successfully.');
            return $this->redirectToRoute('app_planning');
        }

        return $this->render('planning/exchange.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation
        ]);
    }

    #[Route('/reservation/delete/{id}', name: 'app_reservation_delete', methods: ['GET', 'DELETE'])]
    public function delete(
        EntityManagerInterface $entityManager,
        Reservation $reservation
    ): Response {

        $entityManager->remove($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Reservation deleted successfully.');
        return $this->redirectToRoute('app_planning');
    }

    #[Route('/calendar/events', name: 'app_calendar_events')]
    public function getCalendarEvents(EntityManagerInterface $entityManager): JsonResponse
    {
        $reservations = $entityManager->getRepository(Reservation::class)->findAll();
        $events = [];

        foreach ($reservations as $reservation) {
            $startTime = $reservation->getStartTime();
            $endTime = $reservation->getEndTime();

            if ($startTime instanceof \DateTime && $endTime instanceof \DateTime) {
                $events[] = [
                    'title' => sprintf(
                        '%s - %s %s (%s)',
                        $reservation->getResource()->getName(),
                        $reservation->getReservedBy()->getUserProfile()->getFirstName(),
                        $reservation->getReservedBy()->getUserProfile()->getLastName(),
                        $startTime->format('H:i') == '08:00' ? 'AM' : 'PM'
                    ),
                    'start' => $startTime->format('Y-m-d\TH:i:s'),
                    'end' => $endTime->format('Y-m-d\TH:i:s'),
                    'backgroundColor' => $startTime->format('H:i') == '08:00' ? '#007bff' : '#17a2b8',
                    'borderColor' => $startTime->format('H:i') == '08:00' ? '#0056b3' : '#138496'
                ];
            }
        }

        return new JsonResponse($events);
    }

}
